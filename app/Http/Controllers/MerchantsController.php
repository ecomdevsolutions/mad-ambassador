<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use GuzzleHttp\Client;

class MerchantsController extends Controller
{
  public function getMerchants(Request $request)
  {
    $email = $request->email;
    $date_ranges = json_decode($request->dates);

    $query = <<<'GRAPHQL'
    query getCouponCode($email: String!) {
      affiliates(email: $email) {
        paypal_email,
        conversions {
          coupon_code
        }
      }
    }
    GRAPHQL;

    $coupon_code = $this->graphql_query('https://graphql.refersion.com', $query, ['email' => $email], $_ENV['REFERSION_KEY']);
    $commissions = array();
    foreach ($date_ranges as $index => $date) {
      $query = <<<'GRAPHQL'
      query getCommission($email: String!, $from: Int!, $to: Int!) {
        affiliates (email: $email) {
          conversions(created_from: $from, created_to: $to) {
            commission_total
            currency
          }
        }
      }
      GRAPHQL;

      $commission = $this->graphql_query('https://graphql.refersion.com', $query, ['email' => $email, 'from' => $date->from, 'to' =>$date->to], $_ENV['REFERSION_KEY']);
      array_push($commissions, $commission);
    }

    return json_encode([$coupon_code, $commissions]);
  }

  public function getPosition(Request $request)
  {
    $query = <<<'GRAPHQL'
    query {
      affiliates {
        name,
        id,
        email,
        clicks {
          created
        }
      }
    }
    GRAPHQL;

    return $this->graphql_query('https://graphql.refersion.com', $query, [], $_ENV['REFERSION_KEY']);

  }

  public function editPaypalAddress(Request $request)
  {
    $client = new Client();

    $affiliate_id = $request->id;
    $paypal_email = $request->paypal_email;

    $response = $client->request('POST', 'https://www.refersion.com/api/edit_affiliate', [
      'body' => '{"id":"'. $affiliate_id .'","paypal_email":"'. $paypal_email .'"}',
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Refersion-Public-Key' => $_ENV['Refersion_Public_Key'],
        'Refersion-Secret-Key' => $_ENV['Refersion_Secret_Key'],
      ],
    ]);

    return $response->getBody();
  }

  public function validateUser(Request $request)
  {
    $email = $request->email;

    $query = <<<'GRAPHQL'
    query validateUser($email: String!) {
      affiliates(email: $email) {
        id,
        paypal_email,
        status
      }
    }
    GRAPHQL;

    return $this->graphql_query('https://graphql.refersion.com', $query, ['email' => $email], $_ENV['REFERSION_KEY']);
  }

  public function graphql_query(string $endpoint, string $query, array $variables = [], ?string $token = null): array
  {
    $headers = ['Content-Type: application/json'];
    if (null !== $token) {
        $headers[] = "X-Refersion-Key: $token";
    }

    if (false === $data = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode(['query' => $query, 'variables' => $variables]),
        ]
    ]))) {
        $error = error_get_last();
        throw new \ErrorException($error['message'], $error['type']);
    }

    return json_decode($data, true);
  }
}