import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function Onboarding({ ip }: { ip: string }) {
    const adminDNS = `admin.yourdomain.com. IN A ${ip}`;
    const wildcardDNS = `*.yourdomain.com. IN A ${ip}`;

    const copyToClipboard = (text: string) => navigator.clipboard.writeText(text);

    return (
        <div className="flex min-h-screen items-center justify-center bg-background">
            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle>DNS Setup</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="mb-6 rounded-lg bg-muted p-4">
                        <p className="mb-2 text-sm text-muted-foreground">Server IP Address:</p>
                        <p className="font-mono text-xl font-bold text-foreground">{ip}</p>
                    </div>

                    <p className="mb-4">Add these A records to your domain DNS:</p>

                    <div className="mb-4">
                        <div className="mb-2">
                            <span className="text-sm font-medium text-muted-foreground">Admin Subdomain:</span>
                        </div>
                        <div className="flex items-center justify-between rounded border bg-muted p-3">
                            <code className="font-mono text-sm text-foreground">{adminDNS}</code>
                            <Button size="sm" onClick={() => copyToClipboard(adminDNS)}>
                                Copy
                            </Button>
                        </div>
                    </div>

                    <div>
                        <div className="mb-2">
                            <span className="text-sm font-medium text-muted-foreground">Wildcard Subdomain:</span>
                        </div>
                        <div className="flex items-center justify-between rounded border bg-muted p-3">
                            <code className="font-mono text-sm text-foreground">{wildcardDNS}</code>
                            <Button size="sm" onClick={() => copyToClipboard(wildcardDNS)}>
                                Copy
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
