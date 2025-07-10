import type { VerticalNavItems } from '@layouts/types'
import appsAndPages from './apps-and-pages'
import charts from './charts'
import dashboard from './dashboard'
import forms from './forms'
import master from './master'
import order from './order'
import others from './others'
import uiElements from './ui-elements'

export default [...dashboard, ...master, ...order, ...appsAndPages, ...uiElements, ...forms, ...charts, ...others] as VerticalNavItems
