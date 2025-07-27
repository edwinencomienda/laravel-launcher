import { Button } from "@/components/ui/button";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { SidebarTrigger } from "@/components/ui/sidebar";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Appearance, useAppearance } from "@/hooks/use-appearance";
import { SharedData } from "@/types";
import { Link, usePage } from "@inertiajs/react";
import { LogOut, Monitor, Moon, Sun } from "lucide-react";
import { useEffect, useState } from "react";
import { createPortal } from "react-dom";

export const PageHeaderPortal = ({ children }: { children: React.ReactNode }) => {
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    return mounted ? createPortal(children, document.getElementById("header-portal-root")!) : null;
};

export default function AppHeader() {
    const { auth } = usePage<SharedData>().props;
    const { appearance, updateAppearance } = useAppearance();

    if (!auth.user) {
        return null;
    }

    return (
        <div className="flex h-14 w-full items-center border-b px-4">
            <SidebarTrigger />
            <div className="px-2" id="header-portal-root"></div>
            <div className="ml-auto">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="flex items-center gap-2 rounded px-3 py-1.5 text-sm font-medium">
                            <span>{auth.user.name}</span>
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" strokeWidth="2" d="M6 9l6 6 6-6" />
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuItem>
                            <div className="flex flex-col">
                                <span className="font-medium">{auth.user.name}</span>
                                <span className="text-xs text-gray-500">{auth.user.email}</span>
                            </div>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/settings">Settings</Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <div className="flex items-center justify-between px-1.5">
                            <div>Theme</div>
                            <Tabs defaultValue={appearance} onValueChange={(value) => updateAppearance(value as Appearance)}>
                                <TabsList>
                                    <TabsTrigger value="light" asChild>
                                        <Button size="icon" variant="ghost">
                                            <Sun />
                                        </Button>
                                    </TabsTrigger>
                                    <TabsTrigger value="dark" asChild>
                                        <Button size="icon" variant="ghost">
                                            <Moon />
                                        </Button>
                                    </TabsTrigger>
                                    <TabsTrigger value="system" asChild>
                                        <Button size="icon" variant="ghost">
                                            <Monitor />
                                        </Button>
                                    </TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </div>
                        {/* theme swtitcher here... */}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/logout" method="post" className="w-full">
                                <LogOut />
                                Logout
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
