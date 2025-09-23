// ユーザー関連の型定義
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'sales';
  department?: string;
  phone?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

// 物件関連の型定義
export interface Property {
  id: number;
  property_code?: string;
  property_name: string;
  property_type: '店舗' | 'レジ' | '土地' | '事務所' | '区分' | '一棟ビル' | '十地' | '新築ホテル';
  manager_name: string;
  registration_date: string;
  address: string;
  information_source?: string;
  transaction_category: '先物' | '元付' | '売主';
  land_area?: number;
  building_area?: number;
  structure_floors?: string;
  construction_year?: string;
  price: number;
  price_per_unit?: number;
  current_profit?: number;
  prefecture: string;
  city: string;
  nearest_station?: string;
  walking_minutes?: number;
  remarks?: string;
  status: 'available' | 'reserved' | 'sold' | 'suspended';
  created_by: number;
  created_at: string;
  updated_at: string;
  creator?: User;
  images?: PropertyImage[];
  matches?: PropertyMatch[];
}

export interface PropertyImage {
  id: number;
  property_id: number;
  image_path: string;
  image_type: 'exterior' | 'interior' | 'layout' | 'other';
  caption?: string;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

// 顧客関連の型定義
export interface Customer {
  id: number;
  customer_code?: string;
  customer_name: string;
  customer_type: '法人' | '個人' | '自社' | 'エンド法人' | 'エンド（中国系）' | '飲食経営者' | '不動明屋' | '半法商事';
  area_preference?: string;
  property_type_preference?: string;
  detailed_requirements?: string;
  budget_min?: number;
  budget_max?: number;
  yield_requirement?: number;
  contact_person?: string;
  phone?: string;
  email?: string;
  address?: string;
  priority: '高' | '中' | '低';
  status: 'active' | 'negotiating' | 'closed' | 'suspended';
  last_contact_date?: string;
  next_contact_date?: string;
  assigned_to: number;
  created_at: string;
  updated_at: string;
  assigned_user?: User;
  preferences?: CustomerPreference[];
  matches?: PropertyMatch[];
}

export interface CustomerPreference {
  id: number;
  customer_id: number;
  preference_type: 'area' | 'station' | 'structure' | 'age' | 'yield' | 'size' | 'other';
  preference_key: string;
  preference_value: string;
  priority: 'must' | 'want' | 'nice_to_have';
  created_at: string;
  updated_at: string;
}

// マッチング関連の型定義
export interface PropertyMatch {
  id: number;
  property_id: number;
  customer_id: number;
  match_score: number;
  match_reason?: string;
  status: 'matched' | 'presented' | 'interested' | 'rejected' | 'contracted';
  presented_at?: string;
  response_at?: string;
  response_comment?: string;
  created_by: number;
  created_at: string;
  updated_at: string;
  property?: Property;
  customer?: Customer;
  creator?: User;
}

// 活動履歴関連の型定義
export interface Activity {
  id: number;
  user_id: number;
  activity_type: 'property_created' | 'property_updated' | 'customer_created' | 'customer_updated' | 'match_created' | 'presentation' | 'contact' | 'meeting' | 'contract';
  subject_type: 'property' | 'customer' | 'match';
  subject_id: number;
  title: string;
  description?: string;
  activity_date: string;
  created_at: string;
  updated_at: string;
  user?: User;
}

// API レスポンス関連の型定義
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}

// ダッシュボード統計関連の型定義
export interface DashboardStats {
  overview: {
    total_properties: number;
    available_properties: number;
    total_customers: number;
    active_customers: number;
    total_matches: number;
    high_score_matches: number;
    contracts_this_month: number;
  };
  properties: {
    by_status: Record<string, number>;
    by_type: Record<string, number>;
    by_prefecture: Record<string, number>;
    price_distribution: Record<string, number>;
    yield_distribution: Record<string, number>;
  };
  customers: {
    by_status: Record<string, number>;
    by_type: Record<string, number>;
    by_priority: Record<string, number>;
    budget_distribution: Record<string, number>;
  };
  matches: {
    by_status: Record<string, number>;
    score_distribution: Record<string, number>;
    conversion_rates: {
      match_to_presentation: number;
      presentation_to_interest: number;
      interest_to_contract: number;
    };
  };
  alerts: Alert[];
}

export interface Alert {
  type: 'info' | 'warning' | 'error' | 'success';
  title: string;
  message: string;
  count: number;
  action_url?: string;
}

// フォーム関連の型定義
export interface PropertyFormData {
  property_code?: string;
  property_name: string;
  property_type: string;
  manager_name: string;
  registration_date: string;
  address: string;
  information_source?: string;
  transaction_category: string;
  land_area?: number;
  building_area?: number;
  structure_floors?: string;
  construction_year?: string;
  price: number;
  price_per_unit?: number;
  current_profit?: number;
  prefecture: string;
  city: string;
  nearest_station?: string;
  walking_minutes?: number;
  remarks?: string;
  status?: string;
}

export interface CustomerFormData {
  customer_code?: string;
  customer_name: string;
  customer_type: string;
  area_preference?: string;
  property_type_preference?: string;
  detailed_requirements?: string;
  budget_min?: number;
  budget_max?: number;
  yield_requirement?: number;
  contact_person?: string;
  phone?: string;
  email?: string;
  address?: string;
  priority?: string;
  status?: string;
  last_contact_date?: string;
  next_contact_date?: string;
  assigned_to: number;
}

// 検索・フィルター関連の型定義
export interface PropertyFilters {
  property_name?: string;
  property_type?: string;
  prefecture?: string;
  city?: string;
  price_min?: number;
  price_max?: number;
  yield_min?: number;
  yield_max?: number;
  status?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface CustomerFilters {
  customer_type?: string;
  assigned_to?: number;
  budget_min?: number;
  budget_max?: number;
  priority?: string;
  status?: string;
  area_preference?: string;
  long_time_no_contact?: boolean;
  upcoming_contact?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface MatchFilters {
  property_id?: number;
  customer_id?: number;
  status?: string;
  min_score?: number;
  high_score_only?: boolean;
  not_presented_only?: boolean;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
} 