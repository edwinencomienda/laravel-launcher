import { Button } from "@/components/ui/button";

export default function StepGithub({ githubManifest }: { githubManifest: Record<string, any> }) {
    return (
        <div>
            <form action="https://github.com/settings/apps/new" method="post">
                <input type="hidden" name="manifest" value={JSON.stringify(githubManifest)} />
                <Button type="submit">Create GitHub App</Button>
            </form>
        </div>
    );
}
