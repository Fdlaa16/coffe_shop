// types/order.ts

export interface User {
  id: number;
  email: string;
  password?: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export interface Customer {
  id: number;
  code: string;
  user_id: number;
  name: string;
  phone: string;
  address?: string;
  user?: User;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export interface Table {
  id: number;
  code: string;
  name?: string;
  status?: 'available' | 'occupied' | 'reserved' | 'maintenance';
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export interface Menu {
  id: number;
  name: string;
  price: number;
  category: string;
  description?: string;
  photo?: string;
  status?: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export interface OrderItem {
   id: number
  order_id: number
  menu_id: number
  menu_name: string
  qty: number
  unit_price: string
  total_price: string
  size: string
  sugar_level: string
  notes?: string | null
  category: string
}

export interface Order {
  id: number;
  order_number?: string;
  order_date: string;
  customer_id: number;
  table_id: number;
  total_net: number;
  status: 'pending' | 'process' | 'finished' | 'reject';
  payment_method?: string;
  notes?: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
  
  // Relations
  customer?: Customer;
  table?: Table;
  orderItems?: OrderItem[];
  order_items?: OrderItem[]; // alias untuk orderItems
}

// Alias untuk kompatibilitas
export type OrderData = Order;

export interface InvoiceItem {
  id: number;
  code: string;
  invoice_id: number;
  itemable_id: number;
  itemable_type: string;
  qty: number;
  total: number;
  status?: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export interface Invoice {
  id: number;
  invoice_number: string;
  invoice_date: string;
  expired_date?: string;
  order_id: number;
  customer_id: number;
  type?: string;
  total_net: number;
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
  
  // Relations
  customer?: Customer;
  order?: Order;
  invoiceItems?: InvoiceItem[];
}

// Request/Response interfaces
export interface OrderCreateRequest {
  order_date: string;
  customer_id?: number;
  table_id?: number;
  total_net: number;
  status?: string;
  payment_method?: string;
  notes?: string;
  
  // Customer data (jika customer baru)
  customer?: {
    id?: number;
    name: string;
    phone: string;
    address?: string;
    user?: {
      id?: number;
      email: string;
      password?: string;
    };
  };
  
  // Table data
  table?: {
    id?: number;
    code: string;
    name?: string;
    status?: string;
  };
  
  // Order items
  order_items: Array<{
    id?: number;
    order_id?: number;
    menu_id: number;
    menu_name: string;
    qty: number;
    unit_price: number;
    total_price: number;
    size?: string;
    sugar_level?: string;
    notes?: string;
    category?: string;
    type?: string;
    status?: string;
  }>;
}

export interface OrderUpdateRequest extends Partial<OrderCreateRequest> {
  id: number;
  _method?: 'PUT';
}

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
}

// Form interfaces untuk Vue components
export interface OrderFormData {
  order_date: string;
  total_net: number;
  status: string;
  payment_method?: string;
  notes?: string;
  customer: {
    id?: number;
    name: string;
    phone: string;
    address?: string;
    user?: {
      id?: number;
      email: string;
      password?: string;
    };
  };
  table: {
    id?: number;
    code: string;
    name?: string;
    status?: string;
  };
  order_items: Array<{
    id?: number;
    order_id?: number;
    menu_id: number;
    menu_name: string;
    qty: number;
    unit_price: number;
    total_price: number;
    size?: string;
    sugar_level?: string;
    notes?: string;
    category?: string;
    type?: string;
    status?: string;
  }>;
}

// Status options untuk dropdown/select
export const ORDER_STATUSES = [
  { value: 'pending', text: 'Pending', color: 'warning', icon: 'tabler-clock' },
  { value: 'confirmed', text: 'Dikonfirmasi', color: 'info', icon: 'tabler-check' },
  { value: 'processing', text: 'Diproses', color: 'primary', icon: 'tabler-chef-hat' },
  { value: 'completed', text: 'Selesai', color: 'success', icon: 'tabler-check-circle' },
  { value: 'cancelled', text: 'Dibatalkan', color: 'error', icon: 'tabler-x' },
] as const;

export const TABLE_STATUSES = [
  { value: 'available', text: 'Tersedia', color: 'success' },
  { value: 'occupied', text: 'Terisi', color: 'error' },
  { value: 'reserved', text: 'Reservasi', color: 'warning' },
  { value: 'maintenance', text: 'Maintenance', color: 'info' },
] as const;

export const PAYMENT_METHODS = [
  { value: 'cash', text: 'Tunai' },
  { value: 'credit_card', text: 'Kartu Kredit' },
  { value: 'debit_card', text: 'Kartu Debit' },
  { value: 'digital_wallet', text: 'Dompet Digital' },
  { value: 'bank_transfer', text: 'Transfer Bank' },
] as const;
