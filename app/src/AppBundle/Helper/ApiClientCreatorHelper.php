<?php
/**
 * Created by PhpStorm.
 * User: anguidev
 * Date: 2/27/18
 * Time: 2:40 PM
 */
namespace AppBundle\Helper;


use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiClientCreatorHelper
{
    private $client_manager;

    public function __construct( ClientManagerInterface $client_manager)
    {
        $this->client_manager = $client_manager;
    }

    public function createClientForUser(array $callbackUrl, UserInterface $user = null)
    {
        // Create a client using the client manager
        // A secret and a public ID are set automatically by default.
        $client = $this->client_manager->createClient();

        // Set redirect Uris here (optional)
        // The Uris can be defined later or through a dedicated parameter
        $client->setRedirectUris($callbackUrl);

        // Set a grant types here, default is [OAuth2::GRANT_TYPE_AUTH_CODE]
        $client->setAllowedGrantTypes([
            'token',
        ]);

        // If your client class has a client/user relationship, set it here
         if($user) $client->setUser($user);

        // Save the client
        $this->client_manager->updateClient($client);

        // You can return it if needed
        return $client;
    }
}