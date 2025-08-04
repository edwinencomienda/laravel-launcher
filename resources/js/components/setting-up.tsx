const message = "🚀 Launching your awesome site...";

export default function SettingUp() {
    return (
        <div className="flex h-32 items-center justify-center">
            <span className="animate-pulse text-muted-foreground">{message}</span>
        </div>
    );
}
