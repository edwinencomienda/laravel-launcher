import { OnboardingFormData } from "@/types";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

export default function StepUser({ form, setForm }: { form: OnboardingFormData; setForm: (form: any) => void }) {
    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="username" className="block">
                    Default Username
                </Label>
                <Input
                    id="username"
                    value={form.username || ""}
                    onChange={(e) => setForm({ ...form, username: e.target.value })}
                    placeholder="admin"
                />
            </div>
            <div className="space-y-2">
                <Label htmlFor="password" className="block">
                    Default Password
                </Label>
                <Input
                    id="password"
                    type="password"
                    value={form.password || ""}
                    onChange={(e) => setForm({ ...form, password: e.target.value })}
                    placeholder="••••••••"
                />
            </div>
        </div>
    );
}
