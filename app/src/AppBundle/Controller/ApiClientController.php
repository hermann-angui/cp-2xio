<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use GuzzleHttp;
use Symfony\Component\HttpFoundation\Response;

class ApiClientController extends Controller
{

    protected $clientCredentials;
    /**
     * @Route("/client", name="api_client")
     */
    public function indexAction(Request $request)
    {

/*
        $user = $this->get('security.context')->getToken()->getUser();
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setUsername('test1');
        $user->setEmail('test@test1.com');
*/
/*
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=> 1));

        $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris(array("http://localhost"));
    //  $client->setAllowedGrantTypes(array('token', 'authorization_code'));
        $client->setAllowedGrantTypes(array('password'));

        $userManager->updateUser($user);
        $clientManager->updateClient($client);

        $gt = $client->getAllowedGrantTypes();

        $this->clientCredentials = [
            "grant_type"=> $gt[0],
            "client_id" => $client->getId() . "_" . $client->getRandomId(),
            "client_secret" => $client->getSecret(),
            "username" => $user->getUserName(),
            "password"=> $user->getPlainPassword()
        ];*/

      //  return $this->render('AppBundle:Client:index.html.twig', array("credentials" => $this->clientCredentials));
    }


    public function testApiAction(Request $request)
    {

        $http = new GuzzleHttp\Client();

        $request = $http->post('/oauth/v2/token', null,
            array(
                'client_id'     => $this->clientCredentials['client_id'],
                'client_secret' => $this->clientCredentials['client_secret'],
                'grant_type' => $this->clientCredentials['grant_type'],
            )
        );

        // make a request to the token url
        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        echo $responseArr;

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];

        $request = $http->get('/api/test');
        $request->addHeader('Authorization', 'Bearer '.$accessToken);
        $response = $request->send();
        $body = $response->getBody(true);

        return $this->render('AppBundle:Client:testapi.html.twig', array("body" => $body));

    }

}
