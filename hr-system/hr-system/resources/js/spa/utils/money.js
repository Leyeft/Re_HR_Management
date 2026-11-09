// Monthly salary as "฿32,000" (or "฿32,000.50" when there are satang). Empty → "Not set".
export function formatSalary(amount) {
    if (amount === null || amount === undefined || amount === '') return 'Not set';
    const value = Number(amount);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'THB',
        currencyDisplay: 'narrowSymbol',
        minimumFractionDigits: Number.isInteger(value) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(value);
}

// The API only sends `salary` to people allowed to see it (Admin, HR, the person themselves).
export const canSeeSalary = (employee) => !!employee && Object.prototype.hasOwnProperty.call(employee, 'salary');
