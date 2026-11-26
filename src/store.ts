import bcrypt from 'bcryptjs';
import {
  Consignment,
  ConsignmentStatus,
  DeliveryConfirmation,
  DeliveryStatusHistory,
  Driver,
  User,
  Vehicle,
  Waybill,
} from './types.js';

function offsetIsoDate(daysAgo: number, hoursOffset = 0): string {
  const d = new Date(Date.now() - daysAgo * 86400_000 + hoursOffset * 3600_000);
  return d.toISOString();
}

function offsetDateOnly(daysAgo: number): string {
  const d = new Date(Date.now() - daysAgo * 86400_000);
  return d.toISOString().slice(0, 10);
}

// In-memory data store seeded from database/goods_tracking_system.sql
export const db = {
  users: [
    {
      user_id: 1,
      full_name: 'Garage Administrator',
      username: 'admin',
      password_hash: bcrypt.hashSync('admin', 10),
      role: 'Administrator',
      created_at: offsetIsoDate(10),
    },
  ] as User[],

  drivers: [
    {
      driver_id: 1,
      full_name: 'Malam Ibrahim Danladi',
      phone: '08031234567',
      address: 'Funtua Motor Park, Katsina Road, Funtua',
      created_at: offsetIsoDate(10),
    },
    {
      driver_id: 2,
      full_name: 'Aliyu Sani Garba',
      phone: '08029876543',
      address: 'Samaru Main Market, Zaria',
      created_at: offsetIsoDate(10),
    },
    {
      driver_id: 3,
      full_name: 'Usman Babangida',
      phone: '08145566778',
      address: 'Sabon Gari, Zaria',
      created_at: offsetIsoDate(10),
    },
    {
      driver_id: 4,
      full_name: 'Kabiru Mohammed',
      phone: '07033445566',
      address: 'Kano Road Park, Kaduna',
      created_at: offsetIsoDate(10),
    },
  ] as Driver[],

  vehicles: [
    {
      vehicle_id: 1,
      registration_number: 'KTR-428-XA',
      vehicle_type: 'Canter Truck (3-Tonne)',
      description: 'White Mitsubishi Canter with secure canvas cover',
      created_at: offsetIsoDate(10),
    },
    {
      vehicle_id: 2,
      registration_number: 'ZAR-719-AA',
      vehicle_type: 'Toyota Dyna Truck',
      description: 'Heavy-duty commercial goods carrier',
      created_at: offsetIsoDate(10),
    },
    {
      vehicle_id: 3,
      registration_number: 'KAD-882-MK',
      vehicle_type: 'Ford Transit Bus (Cargo)',
      description: 'Enclosed goods transit van for parcel handling',
      created_at: offsetIsoDate(10),
    },
    {
      vehicle_id: 4,
      registration_number: 'FT-105-KTN',
      vehicle_type: 'Mercedes 911 Lorry',
      description: 'Long-distance inter-state haulage vehicle',
      created_at: offsetIsoDate(10),
    },
  ] as Vehicle[],

  consignments: [
    {
      consignment_id: 1,
      tracking_number: 'FGSZ-2026-000001',
      sender_name: 'Alhaji Bashir Umar',
      sender_contact: '08031112233',
      sender_address: 'Plot 12, Samaru Market Road, Zaria',
      recipient_name: 'Hajiya Fatima Bello',
      recipient_contact: '08064445566',
      recipient_address: 'No. 5 Central Market, Funtua',
      goods_description: '10 Bags of High-Yield Grains and Agricultural Produce',
      quantity: 10,
      goods_category: 'Agricultural Produce',
      special_instructions: 'Handle with care. Keep dry.',
      destination: 'Funtua',
      dispatch_date: offsetDateOnly(3),
      driver_id: 1,
      vehicle_id: 1,
      current_status: 'Delivered',
      registered_by: 1,
      created_at: offsetIsoDate(3),
    },
    {
      consignment_id: 2,
      tracking_number: 'FGSZ-2026-000002',
      sender_name: 'Engr. Sani Abdullahi',
      sender_contact: '08052223344',
      sender_address: 'ABU Site 2, Samaru, Zaria',
      recipient_name: 'Musa Garba Enterprises',
      recipient_contact: '08097778899',
      recipient_address: 'Station Road, Kano',
      goods_description: '4 Cartons of Electrical Components and Inverter Accessories',
      quantity: 4,
      goods_category: 'Electronics & Equipment',
      special_instructions: 'Fragile. Do not stack heavily.',
      destination: 'Kano',
      dispatch_date: offsetDateOnly(2),
      driver_id: 2,
      vehicle_id: 2,
      current_status: 'Arrived',
      registered_by: 1,
      created_at: offsetIsoDate(2),
    },
    {
      consignment_id: 3,
      tracking_number: 'FGSZ-2026-000003',
      sender_name: 'Mrs. Zainab Lawal',
      sender_contact: '08123334455',
      sender_address: 'PZ Area, Zaria',
      recipient_name: 'Ibrahim Shehu Traders',
      recipient_contact: '08186665544',
      recipient_address: 'Main Market, Katsina',
      goods_description: '15 Bales of Kaduna Cotton and Tailoring Materials',
      quantity: 15,
      goods_category: 'Textiles & Apparel',
      special_instructions: 'Keep away from moisture.',
      destination: 'Katsina',
      dispatch_date: offsetDateOnly(1),
      driver_id: 1,
      vehicle_id: 1,
      current_status: 'In Transit',
      registered_by: 1,
      created_at: offsetIsoDate(1),
    },
    {
      consignment_id: 4,
      tracking_number: 'FGSZ-2026-000004',
      sender_name: 'Yakubu Dambo',
      sender_contact: '07089990011',
      sender_address: 'Danfodio Road, Samaru, Zaria',
      recipient_name: 'Dr. Aminu Salisu',
      recipient_contact: '07012223344',
      recipient_address: 'Unguwar Rimi, Kaduna',
      goods_description: '2 Packages of Research Laboratory Glassware & Documents',
      quantity: 2,
      goods_category: 'Fragile Goods',
      special_instructions: 'Extremely fragile. Handle with utmost care.',
      destination: 'Kaduna',
      dispatch_date: offsetDateOnly(0),
      driver_id: 3,
      vehicle_id: 3,
      current_status: 'Dispatched',
      registered_by: 1,
      created_at: offsetIsoDate(0, -5),
    },
    {
      consignment_id: 5,
      tracking_number: 'FGSZ-2026-000005',
      sender_name: 'Hassan Abubakar',
      sender_contact: '08034567890',
      sender_address: 'Bomo Road, Samaru, Zaria',
      recipient_name: 'Alhaji Kabir Funtua',
      recipient_contact: '08023456789',
      recipient_address: 'Commercial Layout, Funtua',
      goods_description: '6 Cartons of Automobile Spare Parts',
      quantity: 6,
      goods_category: 'Spare Parts',
      special_instructions: 'Heavy items. Verify item count upon arrival.',
      destination: 'Funtua',
      dispatch_date: offsetDateOnly(0),
      driver_id: 1,
      vehicle_id: 1,
      current_status: 'Registered',
      registered_by: 1,
      created_at: offsetIsoDate(0, -1),
    },
  ] as Consignment[],

  waybills: [
    { waybill_id: 1, consignment_id: 1, waybill_number: 'WB-2026-000001', issue_date: offsetIsoDate(3), issued_by: 1 },
    { waybill_id: 2, consignment_id: 2, waybill_number: 'WB-2026-000002', issue_date: offsetIsoDate(2), issued_by: 1 },
    { waybill_id: 3, consignment_id: 3, waybill_number: 'WB-2026-000003', issue_date: offsetIsoDate(1), issued_by: 1 },
    { waybill_id: 4, consignment_id: 4, waybill_number: 'WB-2026-000004', issue_date: offsetIsoDate(0, -5), issued_by: 1 },
    { waybill_id: 5, consignment_id: 5, waybill_number: 'WB-2026-000005', issue_date: offsetIsoDate(0, -1), issued_by: 1 },
  ] as Waybill[],

  deliveryStatusHistory: [
    { status_id: 1, consignment_id: 1, status: 'Registered', remarks: 'Consignment registered at Funtua Garage, Samaru, Zaria.', updated_by: 1, updated_at: offsetIsoDate(3) },
    { status_id: 2, consignment_id: 1, status: 'Dispatched', remarks: 'Loaded onto Canter Truck KTR-428-XA. Departed Samaru.', updated_by: 1, updated_at: offsetIsoDate(3, 2) },
    { status_id: 3, consignment_id: 1, status: 'In Transit', remarks: 'Transit verified through Katsina express corridor.', updated_by: 1, updated_at: offsetIsoDate(2) },
    { status_id: 4, consignment_id: 1, status: 'Arrived', remarks: 'Vehicle arrived safely at Funtua central delivery terminal.', updated_by: 1, updated_at: offsetIsoDate(2, 6) },
    { status_id: 5, consignment_id: 1, status: 'Delivered', remarks: 'Delivered to recipient Hajiya Fatima Bello in excellent condition.', updated_by: 1, updated_at: offsetIsoDate(1) },

    { status_id: 6, consignment_id: 2, status: 'Registered', remarks: 'Consignment registered at Samaru depot.', updated_by: 1, updated_at: offsetIsoDate(2) },
    { status_id: 7, consignment_id: 2, status: 'Dispatched', remarks: 'Dispatched with driver Aliyu Sani Garba.', updated_by: 1, updated_at: offsetIsoDate(2, 3) },
    { status_id: 8, consignment_id: 2, status: 'In Transit', remarks: 'Passed Wudil junction enroute Kano.', updated_by: 1, updated_at: offsetIsoDate(1, 4) },
    { status_id: 9, consignment_id: 2, status: 'Arrived', remarks: 'Consignment arrived at Kano goods depot awaiting collection.', updated_by: 1, updated_at: offsetIsoDate(0, -4) },

    { status_id: 10, consignment_id: 3, status: 'Registered', remarks: 'Bales registered and weighed.', updated_by: 1, updated_at: offsetIsoDate(1) },
    { status_id: 11, consignment_id: 3, status: 'Dispatched', remarks: 'Dispatched via Canter Truck KTR-428-XA.', updated_by: 1, updated_at: offsetIsoDate(0, -20) },
    { status_id: 12, consignment_id: 3, status: 'In Transit', remarks: 'Currently in transit towards Katsina.', updated_by: 1, updated_at: offsetIsoDate(0, -8) },

    { status_id: 13, consignment_id: 4, status: 'Registered', remarks: 'Packages inspected and sealed.', updated_by: 1, updated_at: offsetIsoDate(0, -5) },
    { status_id: 14, consignment_id: 4, status: 'Dispatched', remarks: 'Dispatched with driver Usman Babangida.', updated_by: 1, updated_at: offsetIsoDate(0, -2) },

    { status_id: 15, consignment_id: 5, status: 'Registered', remarks: 'Goods registered at counter. Awaiting scheduled vehicle loading.', updated_by: 1, updated_at: offsetIsoDate(0, -1) },
  ] as DeliveryStatusHistory[],

  deliveryConfirmations: [
    {
      confirmation_id: 1,
      consignment_id: 1,
      received_by: 'Hajiya Fatima Bello',
      remarks: 'All 10 grain bags received intact without any damage.',
      confirmed_by: 1,
      confirmed_at: offsetIsoDate(1),
    },
  ] as DeliveryConfirmation[],

  counters: {
    driverId: 4,
    vehicleId: 4,
    consignmentId: 5,
    waybillId: 5,
    statusId: 15,
    confirmationId: 1,
  },
};

export const VALID_STATUSES: ConsignmentStatus[] = [
  'Registered',
  'Dispatched',
  'In Transit',
  'Arrived',
  'Delivered',
];

export function generateTrackingNumber(): string {
  const year = new Date().getFullYear();
  let nextId = db.consignments.reduce((max, c) => Math.max(max, c.consignment_id), 0) + 1;
  let candidate = `FGSZ-${year}-${String(nextId).padStart(6, '0')}`;
  while (db.consignments.some((c) => c.tracking_number === candidate)) {
    nextId += Math.floor(Math.random() * 99) + 1;
    candidate = `FGSZ-${year}-${String(nextId).padStart(6, '0')}`;
  }
  return candidate;
}

export function generateWaybillNumber(): string {
  const year = new Date().getFullYear();
  let nextId = db.waybills.reduce((max, w) => Math.max(max, w.waybill_id), 0) + 1;
  let candidate = `WB-${year}-${String(nextId).padStart(6, '0')}`;
  while (db.waybills.some((w) => w.waybill_number === candidate)) {
    nextId += Math.floor(Math.random() * 99) + 1;
    candidate = `WB-${year}-${String(nextId).padStart(6, '0')}`;
  }
  return candidate;
}

export function getStatusOrder(status: string): number {
  const map: Record<string, number> = {
    Registered: 1,
    Dispatched: 2,
    'In Transit': 3,
    Arrived: 4,
    Delivered: 5,
  };
  return map[status] ?? 0;
}

export function isValidStatusTransition(currentStatus: string, newStatus: string): boolean {
  const currentOrder = getStatusOrder(currentStatus);
  const newOrder = getStatusOrder(newStatus);
  if (currentStatus === 'Delivered' && newStatus !== 'Delivered') {
    return false;
  }
  if (newOrder === 0) {
    return false;
  }
  return newOrder >= currentOrder;
}

export function escapeHtml(str: unknown): string {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function nl2brSafe(str: unknown): string {
  return escapeHtml(str).replace(/\r?\n/g, '<br>\n');
}

export function truncateText(str: string, maxLen: number): string {
  if (!str) return '';
  if (str.length <= maxLen) return str;
  return str.slice(0, Math.max(0, maxLen - 3)) + '...';
}

export function getStatusBadge(status: string): string {
  let badgeClass = 'badge-secondary';
  switch (status) {
    case 'Registered':
      badgeClass = 'badge-registered';
      break;
    case 'Dispatched':
      badgeClass = 'badge-dispatched';
      break;
    case 'In Transit':
      badgeClass = 'badge-transit';
      break;
    case 'Arrived':
      badgeClass = 'badge-arrived';
      break;
    case 'Delivered':
      badgeClass = 'badge-delivered';
      break;
  }
  return `<span class="status-badge ${badgeClass}">${escapeHtml(status)}</span>`;
}

export function formatDatetime(datetime: string | null | undefined): string {
  if (!datetime || datetime === '0000-00-00 00:00:00') return 'N/A';
  const d = new Date(datetime);
  if (Number.isNaN(d.getTime())) return 'N/A';
  return d.toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
}

export function formatShortDatetime(datetime: string | null | undefined): string {
  if (!datetime) return 'N/A';
  const d = new Date(datetime);
  if (Number.isNaN(d.getTime())) return 'N/A';
  return d.toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
}

export function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr || dateStr === '0000-00-00') return 'N/A';
  const d = new Date(dateStr.length === 10 ? `${dateStr}T00:00:00` : dateStr);
  if (Number.isNaN(d.getTime())) return 'N/A';
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  });
}

export function formatHeaderDate(): string {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  });
}

export function safeJson(val: unknown): string {
  return JSON.stringify(val).replace(/</g, '\\u003c');
}

export function enrichConsignment(c: Consignment) {
  const waybill = db.waybills.find((w) => w.consignment_id === c.consignment_id) ?? null;
  const driver = c.driver_id ? db.drivers.find((d) => d.driver_id === c.driver_id) ?? null : null;
  const vehicle = c.vehicle_id ? db.vehicles.find((v) => v.vehicle_id === c.vehicle_id) ?? null : null;
  const registrar = db.users.find((u) => u.user_id === c.registered_by) ?? null;
  const confirmation = db.deliveryConfirmations.find((dc) => dc.consignment_id === c.consignment_id) ?? null;
  const confirmer = confirmation
    ? db.users.find((u) => u.user_id === confirmation.confirmed_by) ?? null
    : null;

  return {
    ...c,
    waybill_number: waybill?.waybill_number ?? null,
    issue_date: waybill?.issue_date ?? null,
    driver_name: driver?.full_name ?? null,
    driver_phone: driver?.phone ?? null,
    vehicle_reg: vehicle?.registration_number ?? null,
    vehicle_type: vehicle?.vehicle_type ?? null,
    registrar_name: registrar?.full_name ?? null,
    registrar_role: registrar?.role ?? null,
    received_by: confirmation?.received_by ?? null,
    confirmation_remarks: confirmation?.remarks ?? null,
    confirmed_at: confirmation?.confirmed_at ?? null,
    confirmer_name: confirmer?.full_name ?? null,
  };
}
