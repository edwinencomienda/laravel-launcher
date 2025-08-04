import { OnboardingFormData } from "@/types";
import { Input } from "../ui/input";
import { Label } from "../ui/label";

export default function StepUser({ form, setForm }: { form: OnboardingFormData; setForm: (form: any) => void }) {
    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="name" className="block">
                    Your Name
                </Label>
                <Input id="name" value={form.name || ""} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Admin" />
            </div>
            <div className="space-y-2">
                <Label htmlFor="email" className="block">
                    Your Email
                </Label>
                <Input id="email" value={form.email || ""} onChange={(e) => setForm({ ...form, email: e.target.value })} placeholder="admin" />
            </div>
            <div className="space-y-2">
                <Label htmlFor="password" className="block">
                    Your Password
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
