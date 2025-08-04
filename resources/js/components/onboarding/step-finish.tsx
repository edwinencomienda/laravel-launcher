import { OnboardingFormData } from "@/types";

export default function StepFinish({ onboardingData }: { onboardingData: OnboardingFormData }) {
    return (
        <div>
            Success! Onboarding complete.{" "}
            <a href={`https://${onboardingData.site_domain}`} className="text-blue-600 underline">
                Visit your site here
            </a>
        </div>
    );
}
