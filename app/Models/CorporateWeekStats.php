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
        $response = Http::withBody(
                '{
          "start_date": "1672531200",
          "end_date": "1700999999",
          "corporate_id": "0014P00002Fv3iq"
        }', 'json'
            )
            ->withHeaders([
                'Accept'=> '*/*',
                'User-Agent'=> 'Thunder Client (https://www.thunderclient.com)',
                'Authorization'=> 'eyJraWQiOiIwcVJpR2lrcHFUUmRhXC9uQTdKNmF1bXp0WmJmSVJuWjFYc0RlUjh5bmJHOD0iLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIzZ2s0MWM1dGJ1cmo5NTM4OHQ1MXZuaGVrdiIsInRva2VuX3VzZSI6ImFjY2VzcyIsInNjb3BlIjoiZmFjaWxpdHlcL2ZhYy5yZWFkIGZhY2lsaXR5XC9mYWMud3JpdGUgZmFjaWxpdHlcL2lkZW50aXR5LnJlYWQgZmFjaWxpdHlcL2lkZW50aXR5LndyaXRlIiwiYXV0aF90aW1lIjoxNzAxNzA3OTIzLCJpc3MiOiJodHRwczpcL1wvY29nbml0by1pZHAudXMtd2VzdC0xLmFtYXpvbmF3cy5jb21cL3VzLXdlc3QtMV9kYTlvZ0VuSzEiLCJleHAiOjE3MDE3OTQzMjMsImlhdCI6MTcwMTcwNzkyMywidmVyc2lvbiI6MiwianRpIjoiMDc5NjE3NGUtNmYyYi00ZTE3LWIwZDMtMjUxN2E3NTJmM2RhIiwiY2xpZW50X2lkIjoiM2drNDFjNXRidXJqOTUzODh0NTF2bmhla3YifQ.b8XTXXmfXciKLHBVnvZdnf_FlPCtBHsDvK9zWXmgNOG95dZly4HnDxzkiZw0z4M9u4-y8L9hzvYo1aU56buo7oj9H1nc2t_IMTpLUXd0lQ43D0izRnthfVzXV-hTCu3SZ4HR4il58A2sYkc6HCtwh2UPaNr_TPbbLOuRIO3-IPwdsH-E7amRfyVWhg4OyqXV_0KKTJH3Gpys7APIHwCZ5szMJF2Xp92zkj5bMFyzC6m26B_otDO85YWWV1DvatUMvIT4c2h0Q8kasQUZueYPVWrX8C6AC7rmYfxrkjFz_dWqMP877hQDtiXfiVw8bluK1DOJhEUHkVkSheNIqGl8vA',
                'Content-Type'=> 'application/json',
            ])
            ->get('https://dthcmnqvt1.execute-api.us-west-1.amazonaws.com/corporate_week_stats/by_range');

        return json_decode($response->body(), 'true');
    }
}
