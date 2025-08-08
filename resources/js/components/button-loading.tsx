import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";

export function ButtonLoading({ children, loading, ...props }: { children: React.ReactNode; loading?: boolean; [key: string]: any }) {
    return (
        <Button disabled={loading} {...props}>
            {loading && <Loader2 className="size-4 animate-spin" />}
            {children}
        </Button>
    );
}
