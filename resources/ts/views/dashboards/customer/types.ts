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
