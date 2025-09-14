// types.ts - Interface yang diperbaiki
export interface InvoiceData {
  id?: number;
  invoice_number: string;
  invoice_date: string;
  expired_date: string;
  order_id: number;
  customer_id: number;
  subtotal: number;
  tax: number;
  total_net: number;
  status: string;
  type?: string;
  
  // Additional fields for template compatibility
  order_date?: string; // For template that expects order_date
  payment_method?: string;
  notes?: string;
  
  // Relationships
  customer?: CustomerData;
  invoice_items: InvoiceItemData[];
  table?: TableData;
}

export interface InvoiceItemData {
  id?: number;
  invoice_id: number;
  type: string;
  code: string;
  menu_name: string; // Template expects this field name
  qty: number;
  unit_price: number;
  total_price: number; // Template expects this field name
  total: number; // Keep for compatibility
  size?: string;
  sugar_level?: string;
  notes?: string;
  category?: string;
  status: string;
  name?: string; // Nama item jika ada (alias untuk menu_name)
}

export interface CustomerData {
  id: number;
  name: string;
  phone: string;
  code?: string;
  user?: UserData;
}

export interface UserData {
  id: number;
  email: string;
  name?: string;
}

export interface TableData {
  code: string;
}

// Status options untuk invoice
export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled' | 'pending' | 'process' | 'reject' | 'finished';

export const INVOICE_STATUS_OPTIONS = [
  { value: 'draft', text: 'Draft', color: 'grey', icon: 'tabler-file-draft' },
  { value: 'sent', text: 'Terkirim', color: 'info', icon: 'tabler-send' },
  { value: 'paid', text: 'Lunas', color: 'success', icon: 'tabler-check-circle' },
  { value: 'overdue', text: 'Terlambat', color: 'error', icon: 'tabler-alert-triangle' },
  { value: 'cancelled', text: 'Dibatalkan', color: 'error', icon: 'tabler-x' },
  { value: 'pending', text: 'Menunggu', color: 'warning', icon: 'tabler-clock' },
  { value: 'process', text: 'Diproses', color: 'info', icon: 'tabler-refresh' },
  { value: 'reject', text: 'Ditolak', color: 'error', icon: 'tabler-x' },
  { value: 'finished', text: 'Selesai', color: 'success', icon: 'tabler-check' },
] as const;
