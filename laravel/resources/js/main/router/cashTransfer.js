export default [
    {
        path: "/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "/admin/cash-transfers",
                component: () => import("../views/cash-transfer/index.vue"),
                name: "admin.cash_transfers.index",
                meta: {
                    requireAuth: true,
                    menuParent: "cash_transfer",
                    menuKey: (route) => "cash_transfers",
                    permission: "admin",
                },
            },
        ],
    },
];
