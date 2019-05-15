<?php
namespace Riskio\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Riskio\OAuth2\Client\Provider\Exception\AccountNotProvidedException;
use Riskio\OAuth2\Client\Provider\Exception\Auth0IdentityProviderException;
use Riskio\OAuth2\Client\Provider\Exception\InvalidRegionException;

class Auth0 extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const REGION_US = 'us';
    const REGION_EU = 'eu';
    const REGION_AU = 'au';

    protected $availableRegions = [self::REGION_US, self::REGION_EU, self::REGION_AU];

    protected $region = self::REGION_US;

    protected $account;

    protected function domain()
    {
        if (empty($this->account)) {
            throw new AccountNotProvidedException();
        }
        if (!in_array($this->region, $this->availableRegions)) {
            throw new InvalidRegionException();
        }

        $domain = 'auth0.com';

        if ($this->region !== self::REGION_US) {
            $domain = $this->region . '.' . $domain;
        }

        return 'https://' . $this->account . '.' . $domain;
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->domain() . '/authorize';
    }

    public function getBaseAccessTokenUrl(array $params = [])
    {
        return $this->domain() . '/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain() . '/userinfo';
    }

    public function getDefaultScopes()
    {
        return ['openid', 'email'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            return Auth0IdentityProviderException::fromResponse(
                $response,
                $data['error'] ?: $response->getReasonPhrase()
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new Auth0ResourceOwner($response);
    }
}
