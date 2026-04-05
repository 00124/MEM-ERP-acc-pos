export default [
    {
        path: "/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "/admin/notifications",
                component: () => import("../views/notifications/index.vue"),
                name: "admin.notifications.index",
                meta: {
                    requireAuth: true,
                    menuParent: "notifications",
                    menuKey: (route) => "notifications",
                    permission: "admin",
                },
            },
        ],
    },
];
