import StepDns from "@/components/onboarding/step-dns";
import StepSetup from "@/components/onboarding/step-setup";
import StepSshKey from "@/components/onboarding/step-sshkey";
import StepUser from "@/components/onboarding/step-user";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { OnboardingFormData } from "@/types";
import { useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { Toaster } from "sonner";

const steps = [
    {
        step: "dns",
        label: "DNS Setup",
    },
    {
        step: "ssh_key",
        label: "SSH Key",
    },
    {
        step: "admin_user",
        label: "Setup Admin User",
    },
    {
        step: "setup",
        label: "Setup",
    },
];

export default function Onboarding({ ip, currentStep }: { ip: string; currentStep: OnboardingFormData["step"] }) {
    useEffect(() => {
        // force light mode
        document.documentElement.classList.remove("dark");
    }, []);

    const onboardingForm = useForm({});
    const [form, setForm] = useState<OnboardingFormData>({
        username: "admin",
        password: "password",
        admin_domain: "admin.heyedwin.dev",
        site_domain: "edwin.sites.heyedwin.dev",
        app_name: "My Awesome App",
        repo_url: "https://github.com/edwinencomienda/laravel-demo-deploy",
        step: currentStep,
    });

    useEffect(() => {
        setForm((prev) => ({
            ...prev,
            step: currentStep,
        }));
    }, [currentStep]);

    const handleSubmitForm = () => {
        onboardingForm.transform((data) => ({
            ...form,
        }));

        onboardingForm.post(route("onboarding.store"), {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-background">
            <Toaster />
            <Card className="w-full max-w-lg">
                <CardHeader>
                    <CardTitle>{steps.find((step) => step.step === form.step)?.label}</CardTitle>
                </CardHeader>
                <CardContent>
                    {form.step === "admin_user" && <StepUser form={form} setForm={setForm} />}
                    {form.step === "dns" && <StepDns ip={ip} form={form} setForm={setForm} />}
                    {form.step === "ssh_key" && <StepSshKey form={form} setForm={setForm} />}
                    {form.step === "setup" && <StepSetup />}

                    {form.step !== "setup" && (
                        <div className="mt-6">
                            <Button disabled={onboardingForm.processing} onClick={handleSubmitForm}>
                                Continue
                            </Button>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
