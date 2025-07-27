export default function AppLogo() {
    return (
        <div className="flex items-center gap-2">
            <span className="[&>svg]:size-8 [&>svg]:w-full [&>svg]:max-w-8 group-data-[state=expanded]:[&>svg]:size-8">
                <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0 45C0 20.1472 20.1472 0 45 0H134.803C159.656 0 179.803 20.1472 179.803 45V134.803C179.803 159.656 159.656 179.803 134.803 179.803H45C20.1472 179.803 0 159.656 0 134.803V45Z"
                        fill="#212121"
                    />
                    <path
                        d="M95.7143 35V78.1667H133L83.2857 146V102.833H46L95.7143 35Z"
                        stroke="white"
                        stroke-width="9"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </span>
            <span className="hidden text-xl font-medium group-data-[state=expanded]:block">Rapid</span>
        </div>
    );
}
