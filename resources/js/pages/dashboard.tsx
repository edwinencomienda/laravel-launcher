import { Button } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { PageHeaderPortal } from "@/layouts/app-header";
import { type BreadcrumbItem } from "@/types";
import { Head } from "@inertiajs/react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Dashboard",
        href: "/dashboard",
    },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <PageHeaderPortal>
                <span>Dashboard</span>
            </PageHeaderPortal>

            <div className="grid gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle>User Management</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2">
                        <div>
                            <Input placeholder="Search users" />
                        </div>
                    </CardContent>
                    <CardFooter className="flex gap-2">
                        <Button variant="outline">Click me</Button>
                        <Button>Click me</Button>
                    </CardFooter>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Role Management</CardTitle>
                    </CardHeader>
                    <CardContent>
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Consequatur rerum illum ipsum fugit nulla repellat quo voluptates id
                        perspiciatis voluptate sed, eaque aliquam libero quia blanditiis optio sit debitis nemo.
                    </CardContent>
                    <CardFooter className="flex gap-2">
                        <Button variant="outline">Click me</Button>
                        <Button>Click me</Button>
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}
