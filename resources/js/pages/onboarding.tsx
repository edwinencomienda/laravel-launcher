import StepDns from "@/components/onboarding/step-dns";
import StepFinish from "@/components/onboarding/step-finish";
import StepSshKey from "@/components/onboarding/step-sshkey";
import StepUser from "@/components/onboarding/step-user";
import SettingUp from "@/components/setting-up";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { OnboardingFormData } from "@/types";
import { useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { Toaster } from "sonner";

const steps = [
    {
        step: 1,
        label: "Setup Admin User",
    },
    {
        step: 2,
        label: "DNS Setup",
    },
    {
        step: 3,
        label: "Setup your first app",
    },
    {
        step: 4,
        label: "Finish",
    },
];

export default function Onboarding({ ip, sshPublicKey, onboardingData }: { ip: string; sshPublicKey: string; onboardingData: OnboardingFormData }) {
    useEffect(() => {
        // force light mode
        document.documentElement.classList.remove("dark");
    }, []);

    const onboardingForm = useForm<Record<string, string>>({});
    const [form, setForm] = useState<OnboardingFormData>({
        name: onboardingData.name || "Admin",
        email: onboardingData.email || "admin@email.com",
        password: onboardingData.password || "password",
        admin_domain: onboardingData.admin_domain || "admin.heyedwin.dev",
        site_domain: onboardingData.site_domain || "edwin.sites.heyedwin.dev",
        app_name: onboardingData.app_name || "My Awesome App",
        repo_url: onboardingData.repo_url || "https://github.com/edwinencomienda/laravel-demo-deploy",
        step: onboardingData.step,
        dns_verified: false,
    });

    const step1Valid = form.name && form.email && form.password;
    const step2Valid = form.admin_domain && form.site_domain && form.dns_verified;
    const step3Valid = form.repo_url && form.app_name;

    useEffect(() => {
        setForm((prev) => ({
            ...prev,
            step: onboardingData.step || 1,
        }));
    }, [onboardingData.step]);

    const handleSubmitForm = () => {
        onboardingForm.transform((data) => ({
            ...form,
        }));

        onboardingForm.post(route("onboarding.store"), {
            preserveScroll: true,
            onSuccess: () => {
                setForm((prev) => ({
                    ...prev,
                    step: prev.step + 1,
                }));
            },
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100">
            <Toaster />
            <div className="w-full max-w-lg">
                <Card className="w-full">
                    <CardHeader>
                        <CardTitle>{steps.find((step) => step.step === form.step)?.label}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {form.step === 1 && <StepUser form={form} setForm={setForm} />}
                        {form.step === 2 && <StepDns ip={ip} form={form} setForm={setForm} />}
                        {form.step === 3 && <StepSshKey form={form} setForm={setForm} sshPublicKey={sshPublicKey} />}
                        {form.step === 4 && <StepFinish onboardingData={onboardingData} />}

                        {form.step !== 4 && (
                            <div className="mt-6 flex gap-2">
                                {form.step > 1 && (
                                    <Button variant="outline" onClick={() => setForm({ ...form, step: form.step - 1 })}>
                                        Previous
                                    </Button>
                                )}
                                <Button
                                    disabled={
                                        onboardingForm.processing ||
                                        (form.step === 1 && !step1Valid) ||
                                        (form.step === 2 && !step2Valid) ||
                                        (form.step === 3 && !step3Valid)
                                    }
                                    onClick={handleSubmitForm}
                                >
                                    {form.step === 3 ? "Deploy now" : "Continue"}
                                </Button>
                            </div>
                        )}

                        {onboardingForm.errors.error && <div className="mt-6 block text-red-500">{onboardingForm.errors.error}</div>}
                    </CardContent>
                </Card>

                {form.step === 3 && onboardingForm.processing && (
                    <div className="mt-6 flex items-center justify-center">
                        <SettingUp />
                    </div>
                )}
            </div>
        </div>
    );
}
