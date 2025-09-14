export default [
  {
    title: 'Master',
    icon: { icon: 'tabler-layout-grid-add' },
    children: [
      {
        title: 'Master Data',
        icon: { icon: 'tabler-logs' },
        children: [
          {
            title: 'Pelanggan',
            to: 'dashboards-customer-list',
          },
          {
            title: 'Menu',
            to: 'dashboards-menu-list',
          },
          {
            title: 'Meja',
            to: 'dashboards-table-list',
          }
        ],
      },
    ],
  },
]
