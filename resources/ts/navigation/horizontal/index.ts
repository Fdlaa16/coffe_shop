import type { HorizontalNavItems } from '@layouts/types'
import apps from './apps'
import charts from './charts'
import dashboard from './dashboard'
import forms from './forms'
import master from './master'
import misc from './misc'
import order from './order'
import pages from './pages'
import tables from './tables'
import uiElements from './ui-elements'

export default [...dashboard, ...master, ...order, ...apps, ...pages, ...uiElements, ...forms, ...tables, ...charts, ...misc] as HorizontalNavItems
