import AppLogo from "@/components/app-logo";
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { Link, usePage } from "@inertiajs/react";
import { ChartColumnBig, Globe, Home, Settings, Users } from "lucide-react";

const navItems = [
    {
        title: "Dashboard",
        href: "/dashboard",
        icon: Home,
    },
    {
        title: "Settings",
        href: "/settings",
        icon: Settings,
    },
    {
        title: "Users",
        href: "/users",
        icon: Users,
    },
    {
        title: "Domains",
        href: "/domains",
        icon: Globe,
    },
    {
        title: "Metrics",
        href: "/metrics",
        icon: ChartColumnBig,
    },
];

export function AppSidebar({ ...props }) {
    const page = usePage();
    const computedNavItems = navItems.map((item) => ({
        ...item,
        isActive: page.url.startsWith(item.href),
    }));

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <div className="flex items-center justify-between">
                    <Link href="/dashboard">
                        <AppLogo />
                    </Link>
                </div>
            </SidebarHeader>
            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {computedNavItems.map((item) => (
                                <SidebarMenuItem key={item.href}>
                                    <SidebarMenuButton
                                        asChild
                                        className="hover:bg-gray-200 data-[active=true]:bg-gray-200 hover:dark:bg-white/10 data-[active=true]:dark:bg-white/10"
                                        isActive={item.isActive}
                                    >
                                        <Link href={item.href}>
                                            <item.icon />
                                            {item.title}
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            {/* <SidebarFooter>footer</SidebarFooter> */}
        </Sidebar>
    );
}
