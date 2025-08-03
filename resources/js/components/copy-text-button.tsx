import { Button } from "@/components/ui/button";
import { Check } from "lucide-react";
import { useState } from "react";

export default function CopyTextButton({ text, disabled, className }: { text: string; disabled?: boolean; className?: string }) {
    const [copied, setCopied] = useState(false);

    const copyToClipboard = () => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Button size="sm" onClick={copyToClipboard} variant="outline" disabled={disabled} className={className}>
            {copied ? (
                <span className="flex items-center gap-1">
                    <Check size={16} />
                    Copied
                </span>
            ) : (
                "Copy"
            )}
        </Button>
    );
}
