export default [
{ heading: 'Order' },
  {
    title: 'Order Data',
    icon: { icon: 'tabler-logs' },
    children: [
      { title: 'Orders', to: 'dashboards-order-list', },
      { title: 'Invoice', to: 'dashboards-invoice-list', },
    ],
  },
]
