<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DnsVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string',
            'ip' => 'required|string|ip',
        ]);

        $domain = $request->input('domain');
        $expectedIp = $request->input('ip');
        $expectedIp = '5.223.75.35';

        try {
            // Remove protocol if present
            $domain = preg_replace('/^https?:\/\//', '', $domain);

            // Get DNS records
            $dnsRecords = dns_get_record($domain, DNS_A);

            if (empty($dnsRecords)) {
                return response()->json([
                    'verified' => false,
                    'message' => 'No A records found for domain',
                ]);
            }

            // Check if any A record matches the expected IP
            $verified = collect($dnsRecords)->contains('ip', $expectedIp);

            return response()->json([
                'verified' => $verified,
                'message' => $verified ? 'DNS record verified' : 'DNS record does not match expected IP',
                'found_records' => collect($dnsRecords)->pluck('ip')->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'verified' => false,
                'message' => 'Failed to verify DNS record: '.$e->getMessage(),
            ], 500);
        }
    }
}
