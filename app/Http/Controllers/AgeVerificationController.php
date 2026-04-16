<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\UserVerificationInfo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AgeVerificationController extends Controller
{    
    
    public function enable(Request $request): JsonResponse
    {           
        $request->validate([
            'vpassword' => 'required|string',
            'birthdate' => 'required|string'
        ]);

        $issuerIdentifier = env('ISSUER_UUID');

        $issuer = $request->header('X-Age-Verification') ?? null;

        if ($issuer == null || $issuer !== $issuerIdentifier) {
            // return response()->json(['message' => 'Not authorized'], 401);
        }
        
        // TODO Check if vpassword match issuer ($issuer) password, can be a issuer list or DB of issuers

        $zkeys = file_get_contents(storage_path('app/private/build/age.zkey'));

        $key64 = base64_encode($zkeys);
        
        $grafs = file_get_contents(storage_path('app/private/build/age.graf'));

        $graph = base64_encode($grafs);

        $secretKey = base64_decode(env('CREDENTIALS_SECRET_KEY'));
        $publicKey = base64_decode(env('CREDENTIALS_PUBLIC_KEY'));

        $publicKeyBase64 = env('CREDENTIALS_PUBLIC_KEY');

        $leaves = [];
        
        try {
            $path = storage_path('app/private/merkle_leaf.json');

            $json = File::get($path);
        
            $leaf = json_decode($json, true);
        
        } catch (Exception $e) {
            return response()->json(['Error' => 'leaf not available'], 500);
        }
                          
        $credential = [
            'issuer' => $issuer,
            'claims' => [
                'birthday' => $request->input('birthdate')                
            ],            
            'issued' => Carbon::now()->format('d.m.Y'),
            'leaves' => $leaf
        ];
                
        $json = json_encode($credential, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $signature = base64_encode(sodium_crypto_sign_detached($json, $secretKey));
                
        $credentials['proof'] = [
            'publickey' => $publicKeyBase64,
            'signature' => $signature
        ];

        $credentials['field'] = base64_encode($json);

        $creds = base64_encode(json_encode($credentials));
                
        return response()->json([
            'graph' => $graph,
            'vzkey' => $key64,
            'creds' => $creds
        ]);
    }

    public function leaf(Request $request): JsonResponse
    {        
        if ($request->input('member') && $request->input('lindex')) {
     
            $addr = [];
            $path = storage_path('app/private/merkle_leaf.json');

            $addr['member'] = $request->input('member');
            $addr['lindex'] = $request->input('lindex');

            file_put_contents($path, json_encode($addr, JSON_PRETTY_PRINT |  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return response()->json([['message' => 'ok']]);
        }
        
        return response()->json(['message' => 'Webhook test']);
    }
    
}