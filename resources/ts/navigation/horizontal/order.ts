export default [
  {
    title: 'Order',
    icon: { icon: 'tabler-layout-grid-add' },
    children: [
      {
        title: 'Order Data',
        icon: { icon: 'tabler-logs' },
        children: [
          {
            title: 'Orders',
            to: 'dashboards-order-list',
          },
        ],
      },
    ],
  },
]
