import { AppSidebar } from "@/components/app-sidebar";
import { SidebarProvider } from "@/components/ui/sidebar";
import AppHeader from "@/layouts/app-header";

export default function AppLayout({ children }: { children: React.ReactNode }) {
    return (
        <SidebarProvider
            style={
                {
                    "--sidebar-width": "14rem",
                    "--sidebar-width-mobile": "20rem",
                } as any
            }
        >
            <AppSidebar />
            <main className="w-full">
                <AppHeader />
                <div className="p-4">{children}</div>
            </main>
        </SidebarProvider>
    );
}
