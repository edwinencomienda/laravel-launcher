import { GithubRepo, OnboardingFormData } from "@/types";
import { Autocomplete } from "../ui/autocomplete";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

export default function StepSelectRepo({
    form,
    setForm,
    repos,
}: {
    form: OnboardingFormData;
    setForm: (form: OnboardingFormData) => void;
    repos: GithubRepo[];
}) {
    return (
        <div className="space-y-4">
            <div>
                <Label htmlFor="app-name">App Name</Label>
                <Input
                    id="app-name"
                    value={form.app_name || ""}
                    onChange={(e) => setForm({ ...form, app_name: e.target.value })}
                    placeholder="My Awesome App"
                />
                {form.app_name === "" && <div className="mt-1 text-sm text-red-500">App name is required</div>}
            </div>
            <div>
                <Label htmlFor="repo-url">Repository</Label>
                <Autocomplete
                    options={
                        repos?.map((r) => ({
                            label: r.full_name,
                            value: r.full_name,
                        })) || []
                    }
                    value={form.repo_name}
                    onChange={(v) => setForm({ ...form, repo_name: v })}
                    placeholder="Select repository"
                    searchPlaceholder="Search repositories..."
                />
                {form.repo_name === "" && <div className="mt-1 text-sm text-red-500">Repository is required</div>}
            </div>
            <div>
                <Label htmlFor="repo-branch">Branch</Label>
                <Input
                    id="repo-branch"
                    value={form.repo_branch || ""}
                    onChange={(e) => setForm({ ...form, repo_branch: e.target.value })}
                    placeholder="Enter branch name"
                />
            </div>
        </div>
    );
}
