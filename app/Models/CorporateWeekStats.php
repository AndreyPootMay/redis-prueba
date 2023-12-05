<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

final class CorporateWeekStats extends Model
{
    use HasFactory;

    public static function loadModels(): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic M2drNDFjNXRidXJqOTUzODh0NTF2bmhla3Y6bDluYmJuNTFnMTQyMG1vZTRvMWY1djJtN3J0NGdpdnJ1bDJkaGZvMGk5anBsNDZoYTI0',
            'Cookie' => 'XSRF-TOKEN=4a47a55d-8d88-48e0-9c40-e706589304f6',
        ])
        ->asForm()
        ->post('https://dev-verified-fac-domain.auth.us-west-1.amazoncognito.com/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => '3gk41c5tburj95388t51vnhekv',
            'redirect_uri' => 'https://auth.verifiedplatform.com/',
            'scope' => 'facility/identity.write facility/identity.read facility/fac.write facility/fac.read',
        ]);

        $responseLogin = json_decode($response->body(), 'true');

        $response = Http::withBody(
                '{
          "start_date": "1672531200",
          "end_date": "1701561600",
          "corporate_id": "0014P00002Fv3iq"
        }', 'json'
            )
            ->withHeaders([
                'Accept'=> '*/*',
                'User-Agent'=> 'Thunder Client (https://www.thunderclient.com)',
                'Authorization'=> $responseLogin['access_token'],
                'Content-Type'=> 'application/json',
            ])
            ->get('https://dthcmnqvt1.execute-api.us-west-1.amazonaws.com/corporate_week_stats/by_range');

        return json_decode($response->body(), 'true');
    }
}
