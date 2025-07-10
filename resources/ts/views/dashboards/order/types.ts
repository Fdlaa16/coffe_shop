export interface OrderData {
  id: number; 
  customer: CustomerData; 
  table: Table; 
  orderItem: OrderItem[];
  order_date: string; 
  total_net: string; 
  status: boolean; 
}

export interface CustomerData {
  id: number; 
  user: User;
  name: string;
  phone: string;
}

export interface User {
  id?: number;
  email: string;
  password?: string;
}

export interface Table {
  id?: number;
  code: string;
  name: string;
  status: boolean;
}

export interface OrderItem {
  id?: number;
  code: string;
  qty: string;
  price: string;
  status: boolean;
}
