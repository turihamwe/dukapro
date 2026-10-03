# Supplier credit & accounts payable

Isolated from POS and cash sales. Enabled only when:

1. Platform master `supplier_credit_platform_enabled` is on (superadmin), and
2. Tenant `settings.supplier_credit_mode` is on (business profile).

Routes use `supplier.credit` middleware and `access-supplier-credit` gate.

Stock increases reuse `ProductInventoryService::topUpStock()` / batch mode — no sale records are created.

## Navigation (Purchases menu)

When the gate passes, sidebar shows **Purchases** with:

- **Purchase Receives** (hover: Restock) — `tenant.supplier-credit.receives.create`
- **Bills** — open / partial / paid AP (`tenant.supplier-credit.bills.*`)
- **Payments Made** — payment ledger (`tenant.supplier-credit.payments.index`)
- **Vendors** — `tenant.supplier-credit.vendors.index`
- **Overview** — period stats + activity timeline (`tenant.supplier-credit.overview.index`)

Bill detail supports **Make payment** modal for partial installments.
