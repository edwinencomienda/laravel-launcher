import { OnboardingFormData } from "@/types";
import { useForm } from "@inertiajs/react";
import { CopyTextButton } from "../copy-text-button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

export default function StepSshKey({
    form,
    setForm,
    sshPublicKey,
}: {
    form: OnboardingFormData;
    setForm: (form: any) => void;
    sshPublicKey: string;
}) {
    const verifySshKeyForm = useForm({});
    const handleVerifySshKey = () => {
        verifySshKeyForm.post(route("onboarding.verify-ssh-key"), {
            preserveScroll: true,
        });
    };

    return (
        <div className="w-full space-y-4">
            <div>
                <p className="mb-2 text-sm text-muted-foreground">
                    Copy the public SSH key below and add it to your <b>Git repository or Git account</b> (e.g. GitHub, GitLab, etc) as a deploy key
                    or SSH key.
                </p>
                <div className="flex items-center gap-2 rounded border bg-muted p-3">
                    <code className="font-mono text-sm break-all">{sshPublicKey}</code>
                    <CopyTextButton text={sshPublicKey} />
                </div>
            </div>
            <div className="space-y-2">
                <Label htmlFor="site-name" className="block">
                    Your first app name
                </Label>
                <Input
                    id="app-name"
                    value={form.app_name || ""}
                    onChange={(e) => setForm({ ...form, app_name: e.target.value })}
                    placeholder="My Awesome App"
                />
            </div>
            <div className="space-y-2">
                <Label htmlFor="git-repo" className="block">
                    Git Repository URL
                </Label>
                <Input
                    id="git-repo"
                    value={form.repo_url || ""}
                    onChange={(e) => setForm({ ...form, repo_url: e.target.value })}
                    placeholder="https://github.com/{username}/{repo_name}.git"
                />
            </div>
        </div>
    );
}
