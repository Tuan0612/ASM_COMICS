<?php


namespace App\Controller;

use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class ApiController extends AbstractController
{

    /**
     * @Route("/anime", name="anime_list")
     */
    public function listAnime(): Response
    {
        // Send a request to the API to get the anime data
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://kitsu.io/api/edge/anime?page[limit]=20&page[offset]=0');
        $animeList = $response->toArray();

        // Render the view with the list of anime.
        return $this->render('anime/list.html.twig', [
            'animeList' => $animeList['data'], // Pass the anime data to the template
        ]);
    }

    /**
     * @Route("/anime/{id}", name="anime_detail")
     */
    public function detailAnime($id): Response
    {
        // Gửi yêu cầu API để lấy thông tin anime cụ thể dựa trên ID
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://kitsu.io/api/edge/anime/' . $id);
        $animeDetail = $response->toArray();

        // Render the view with the anime details.
        return $this->render('anime/detail.html.twig', [
            'animeDetail' => $animeDetail['data']['attributes'], // Truyền thông tin anime vào view
        ]);
    }
}
