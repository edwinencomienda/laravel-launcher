import { OnboardingMetadata } from "@/types";
import { router, usePoll } from "@inertiajs/react";
import { Button } from "../ui/button";

export default function StepFinish({ onboardingData }: { onboardingData: OnboardingMetadata }) {
    if (!["completed", "failed"].includes(onboardingData.status)) {
        usePoll(5000);
        return <div>{onboardingData.setup_status_message}</div>;
    }

    if (onboardingData.status === "failed") {
        return (
            <div>
                Onboarding failed. {onboardingData.setup_status_message}
                <Button variant="outline" onClick={() => router.visit(route("onboarding.redeploy"))}>
                    Retry
                </Button>
            </div>
        );
    }

    return (
        <div>
            Success! Onboarding complete.{" "}
            <a href={`https://${onboardingData.site_domain}`} className="text-blue-600 underline">
                Visit your site here
            </a>
        </div>
    );
}
