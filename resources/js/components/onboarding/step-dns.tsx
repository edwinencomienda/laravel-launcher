import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { OnboardingFormData } from "@/types";
import { useState } from "react";
import { toast } from "sonner";
import { CopyTextButton } from "../copy-text-button";
import { Badge } from "../ui/badge";

export default function StepDns({ ip, form, setForm }: { ip: string; form: OnboardingFormData; setForm: (form: OnboardingFormData) => void }) {
    const [verifying, setVerifying] = useState(false);
    const [adminVerified, setAdminVerified] = useState<boolean | null>(null);
    const [websiteVerified, setWebsiteVerified] = useState<boolean | null>(null);

    const verifyDNS = async () => {
        if (!form.admin_domain && !form.site_domain) {
            toast.error("Please enter at least one domain to verify");
            return;
        }

        setVerifying(true);

        try {
            const promises = [];

            if (form.admin_domain) {
                promises.push(
                    fetch(
                        route("verify-dns", {
                            domain: form.admin_domain,
                            ip,
                        }),
                    )
                        .then((res) => res.json())
                        .then((data) => ({ type: "admin", success: data.verified })),
                );
            }

            if (form.site_domain) {
                promises.push(
                    fetch(
                        route("verify-dns", {
                            domain: form.site_domain,
                            ip,
                        }),
                    )
                        .then((res) => res.json())
                        .then((data) => ({ type: "wildcard", success: data.verified })),
                );
            }

            const results = await Promise.all(promises);

            results.forEach((result) => {
                if (result.type === "admin") {
                    setAdminVerified(result.success);
                } else if (result.type === "wildcard") {
                    setWebsiteVerified(result.success);
                }
            });

            const allVerified = results.every((r) => r.success);
            if (allVerified) {
                toast.success("All DNS records verified successfully!");
            } else {
                toast.error("Some DNS records failed verification");
            }
        } catch (error) {
            toast.error("Failed to verify DNS records");
            console.error("DNS verification error:", error);
        } finally {
            setVerifying(false);
        }
    };

    const getVerificationStatus = (verified: boolean | null) => {
        if (verified === null) return null;
        return verified ? (
            <Badge variant="default" className="bg-green-500">
                Verified
            </Badge>
        ) : (
            <Badge variant="destructive">Failed</Badge>
        );
    };

    return (
        <>
            <div className="mb-6 rounded-lg bg-muted p-4">
                <p className="mb-2 text-sm text-muted-foreground">Server IP Address:</p>
                <p className="font-mono text-xl font-bold text-foreground">{ip}</p>
            </div>

            <div className="mb-6 space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="admin-domain">Admin Domain</Label>
                    <Input
                        id="admin-domain"
                        value={form.admin_domain}
                        onChange={(e) => setForm({ ...form, admin_domain: e.target.value })}
                        placeholder="admin.yourdomain.com"
                        className="mt-1"
                    />
                    <p className="text-sm text-muted-foreground">This is the domain that will be used to access the admin panel.</p>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wildcard-domain">Your site domain</Label>
                    <Input
                        id="wildcard-domain"
                        value={form.site_domain}
                        onChange={(e) => setForm({ ...form, site_domain: e.target.value })}
                        placeholder="yourdomain.com"
                        className="mt-1"
                    />
                    <p className="text-sm text-muted-foreground">Your main domain for sites</p>
                </div>
            </div>

            <div className="mb-4 flex items-center justify-between">
                <p>Add these A records to your domain DNS:</p>
                <Button onClick={verifyDNS} disabled={verifying || (!form.admin_domain && !form.site_domain)} size="sm">
                    {verifying ? "Verifying..." : "Verify DNS"}
                </Button>
            </div>

            <div className="mb-4">
                <div className="mb-2 flex items-center justify-between">
                    <span className="text-sm font-medium text-muted-foreground">Admin domain:</span>
                    {getVerificationStatus(adminVerified)}
                </div>
                <div className="space-y-2">
                    <div className="flex items-center justify-between rounded border bg-muted p-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-muted-foreground">Name:</span>
                            <code className="font-mono text-sm text-foreground">{form.admin_domain}.</code>
                        </div>
                        <CopyTextButton text={`${form.admin_domain}.`} disabled={!form.admin_domain} />
                    </div>
                    <div className="flex items-center justify-between rounded border bg-muted p-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-muted-foreground">Value:</span>
                            <code className="font-mono text-sm text-foreground">{ip}</code>
                        </div>
                        <CopyTextButton text={ip} disabled={!ip} />
                    </div>
                </div>
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between">
                    <span className="text-sm font-medium text-muted-foreground">Your site domain:</span>
                    {getVerificationStatus(websiteVerified)}
                </div>
                <div className="space-y-2">
                    <div className="flex items-center justify-between rounded border bg-muted p-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-muted-foreground">Name:</span>
                            <code className="font-mono text-sm text-foreground">{form.site_domain}.</code>
                        </div>
                        <CopyTextButton text={`${form.site_domain}.`} disabled={!form.site_domain} />
                    </div>
                    <div className="flex items-center justify-between rounded border bg-muted p-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-muted-foreground">Value:</span>
                            <code className="font-mono text-sm text-foreground">{ip}</code>
                        </div>
                        <CopyTextButton text={ip} disabled={!ip} />
                    </div>
                </div>
            </div>
        </>
    );
}
