<?php

namespace App\Http\Controllers;

use App\Rules\FqdnRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DnsVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => ['required', 'string', new FqdnRule],
            'ip' => 'required|string|ip',
        ]);

        $domain = preg_replace('/^https?:\/\//', '', $request->input('domain'));
        $expectedIp = config('app.test_verify_ip') ?? $request->input('ip');

        $nameservers = [
            '1.1.1.1', // Cloudflare
            '8.8.8.8', // Google
            '9.9.9.9', // Quad9
        ];

        $verified = false;
        $recordsByNS = [];

        foreach ($nameservers as $ns) {
            $cmd = escapeshellcmd("dig @$ns +short A $domain");
            $output = shell_exec($cmd);
            $ips = array_filter(array_map('trim', explode("\n", $output)));

            $recordsByNS[$ns] = $ips;

            if (in_array($expectedIp, $ips)) {
                $verified = true;
            }
        }

        return response()->json([
            'verified' => $verified,
            'message' => $verified
                ? 'DNS record verified via public resolvers.'
                : 'DNS record not yet propagated to public resolvers.',
            'records' => $recordsByNS,
        ]);
    }
}
