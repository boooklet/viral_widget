<?php
class ViralWidget
{
    const TIME_KEEP_COOKIES = 86400 * 30; // 86400 = 1 day
    const REGISTRATION_KEY = 'registration_code'; // for registration code
    const RECOMMENDATION_KEY = 'recommendation_code'; // for recommendation code

    private $viral_campaign_hash_id;
    private $routing_match;
    private $get;
    private $cookies;
    private $cookie_registration_key_name;
    private $cookie_recommendation_key_name;
    private $registration_code_value;
    private $recommendation_code_value;
    private $cookies_to_set;

    public function __construct(string $viral_campaign_hash_id, array $params = [])
    {
        $this->viral_campaign_hash_id = $viral_campaign_hash_id;
        $this->routing_match = $params['routing_match'] ?? [];
        $this->get = $params['get'] ?? [];
        $this->cookies = $params['cookie'] ?? [];

        $this->setCookiesKeys();
        $this->setCodesValues();
        $this->generateCookiesToSet();
        $this->setCookies();
    }

    public function widget(array $params = [])
    {
        if ($this->isSigningUpMember()) {
            return $this->recommendationWidget($params);
        } else {
            return $this->registrationForm($params);
        }
    }

    private function setCookiesKeys()
    {
        // Need to use cookies names prefix in case when two viral widgets in this same domain
        $pefix = $this->getKeysPrefix();
        $this->cookie_registration_key_name = $pefix . '_' . self::REGISTRATION_KEY;
        $this->cookie_recommendation_key_name = $pefix . '_' . self::RECOMMENDATION_KEY;
    }

    private function setCodesValues()
    {
        $this->registration_code_value = $this->routing_match[self::REGISTRATION_KEY] ?? $this->cookies[$this->cookie_registration_key_name] ?? null;
        $this->recommendation_code_value = $this->get[self::RECOMMENDATION_KEY] ?? $this->cookies[$this->cookie_recommendation_key_name] ?? null;
    }

    private function generateCookiesToSet()
    {
        $this->cookies_to_set = [];
        // Create or update cookies only if the value has changed
        // not to refresh the expiration date of cookies for each widget display.
        if ($this->registration_code_value) {
            $this->cookies_to_set[] = [
                'name' => $this->cookie_registration_key_name,
                'value' => $this->registration_code_value,
            ];
        }

        if ($this->recommendation_code_value) {
            $this->cookies_to_set[] = [
                'name' => $this->cookie_recommendation_key_name,
                'value' => $this->recommendation_code_value,
            ];
        }
    }

    private function setCookies()
    {
        foreach ($this->cookies_to_set as $cookie) {
            $this->setCookieWhenValueChange($cookie['name'], $cookie['value']);
        }
    }

    private function recommendationWidget($params)
    {
        $member_data = (new ViralWidgetRequest())->getMemberData($this->recommendation_code_value);
        if ($member_data) {
            return (new ViralWidgetHtml($params, $this->get))->recommendationWidget($member_data);
        } else {
            return $this->registrationForm($params);
        }
    }

    private function registrationForm($params)
    {
        $data = [
            'viral_campaign_hash_id' => $this->viral_campaign_hash_id,
            'registration_code_value' => $this->registration_code_value,
        ];

        return (new ViralWidgetHtml($params, $this->get))->registrationForm($data);
    }

    private function setCookieWhenValueChange($name, $value)
    {
        if ($this->isCookieExistsWithEqualValue($name, $value) or self::isTestMode()) {
            return;
        }

        setcookie($name, $value, time() + self::TIME_KEEP_COOKIES, '/');
    }

    private function getKeysPrefix()
    {
        return substr($this->viral_campaign_hash_id, 0, 6);
    }

    private function isSigningUpMember()
    {
        return $this->recommendation_code_value;
    }

    private function isCookieExistsWithEqualValue($name, $value)
    {
        return isset($this->cookies[$name]) and $this->cookies[$name] == $value;
    }

    public static function isTestMode()
    {
        return defined('IS_TEST_ENV') and IS_TEST_ENV == true;
    }

    // For tests
    public function getParams()
    {
        return [
            'viral_campaign_hash_id' => $this->viral_campaign_hash_id,
            'routing_match' => $this->routing_match,
            'get' => $this->get,
            'cookies' => $this->cookies,
            'cookie_registration_key_name' => $this->cookie_registration_key_name,
            'cookie_recommendation_key_name' => $this->cookie_recommendation_key_name,
            'registration_code_value' => $this->registration_code_value,
            'recommendation_code_value' => $this->recommendation_code_value,
            'cookies_to_set' => $this->cookies_to_set,
        ];
    }
}