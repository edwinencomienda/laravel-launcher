import StepDns from "@/components/onboarding/step-dns";
import StepFinish from "@/components/onboarding/step-finish";
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
        name: "Admin",
        email: "admin@email.com",
        password: "password",
        admin_domain: "admin.heyedwin.dev",
        site_domain: "edwin.sites.heyedwin.dev",
        app_name: "My Awesome App",
        repo_url: "https://github.com/edwinencomienda/laravel-demo-deploy",
        step: onboardingData.step,
    });

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
            <Card className="w-full max-w-lg">
                <CardHeader>
                    <CardTitle>{steps.find((step) => step.step === form.step)?.label}</CardTitle>
                </CardHeader>
                <CardContent>
                    {form.step === 1 && <StepUser form={form} setForm={setForm} />}
                    {form.step === 2 && <StepDns ip={ip} form={form} setForm={setForm} />}
                    {form.step === 3 && <StepSshKey form={form} setForm={setForm} sshPublicKey={sshPublicKey} />}
                    {form.step === 4 && <StepFinish />}

                    {form.step !== 4 && (
                        <div className="mt-6 flex gap-2">
                            {form.step > 1 && (
                                <Button variant="outline" onClick={() => setForm({ ...form, step: form.step - 1 })}>
                                    Previous
                                </Button>
                            )}
                            <Button disabled={onboardingForm.processing} onClick={handleSubmitForm}>
                                {form.step === 3 ? "Submit and Create App" : "Continue"}
                            </Button>
                        </div>
                    )}

                    {onboardingForm.errors.error && <div className="mt-6 block text-red-500">{onboardingForm.errors.error}</div>}
                </CardContent>
            </Card>
        </div>
    );
}
