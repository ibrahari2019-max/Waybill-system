export type ConsignmentStatus =
  | 'Registered'
  | 'Dispatched'
  | 'In Transit'
  | 'Arrived'
  | 'Delivered';

export interface User {
  user_id: number;
  full_name: string;
  username: string;
  password_hash: string;
  role: string;
  created_at: string;
}

export interface SessionUser {
  user_id: number;
  username: string;
  full_name: string;
  role: string;
}

export interface Driver {
  driver_id: number;
  full_name: string;
  phone: string;
  address: string | null;
  created_at: string;
}

export interface Vehicle {
  vehicle_id: number;
  registration_number: string;
  vehicle_type: string;
  description: string | null;
  created_at: string;
}

export interface Consignment {
  consignment_id: number;
  tracking_number: string;
  sender_name: string;
  sender_contact: string;
  sender_address: string | null;
  recipient_name: string;
  recipient_contact: string;
  recipient_address: string | null;
  goods_description: string;
  quantity: number;
  goods_category: string | null;
  special_instructions: string | null;
  destination: string;
  dispatch_date: string | null;
  driver_id: number | null;
  vehicle_id: number | null;
  current_status: ConsignmentStatus;
  registered_by: number;
  created_at: string;
}

export interface Waybill {
  waybill_id: number;
  consignment_id: number;
  waybill_number: string;
  issue_date: string;
  issued_by: number;
}

export interface DeliveryStatusHistory {
  status_id: number;
  consignment_id: number;
  status: ConsignmentStatus;
  remarks: string | null;
  updated_by: number;
  updated_at: string;
}

export interface DeliveryConfirmation {
  confirmation_id: number;
  consignment_id: number;
  received_by: string;
  remarks: string | null;
  confirmed_by: number;
  confirmed_at: string;
}

export interface FlashMessage {
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
}
