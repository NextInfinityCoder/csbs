import type { Station } from './station';

export type UserType = 'admin' | 'cashier' | 'encoder';

export type Cashier = {
    id: number;
    name: string;
    email: string;
    type: UserType;
    station_id: number | null;
    station: Pick<Station, 'id' | 'name'> | null;
    created_at: string;
    updated_at: string;
};
