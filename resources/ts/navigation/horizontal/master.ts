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
            title: 'Customers',
            to: 'dashboards-customer-list',
          },
          {
            title: 'Foods',
            to: 'dashboards-food-list',
          },
          {
            title: 'Drinks',
            to: 'dashboards-drink-list',
          },
          {
            title: 'Tables',
            to: 'dashboards-table-list',
          }
        ],
      },
    ],
  },
]
