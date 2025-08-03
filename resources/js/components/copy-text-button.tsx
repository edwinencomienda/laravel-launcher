import { Button } from "@/components/ui/button";
import { Check } from "lucide-react";
import { useState } from "react";

export default function CopyTextButton({ text, disabled, className }: { text: string; disabled?: boolean; className?: string }) {
    const [copied, setCopied] = useState(false);

    const copyToClipboard = () => {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
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
