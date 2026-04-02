ALTER TABLE coa_accounts
    ADD COLUMN normal_balance ENUM('DEBIT','CREDIT') NOT NULL DEFAULT 'DEBIT' AFTER account_category,
    ADD COLUMN report_type ENUM('BALANCE_SHEET','PROFIT_LOSS','EQUITY','OFF_BALANCE') NOT NULL DEFAULT 'BALANCE_SHEET' AFTER normal_balance,
    ADD COLUMN report_section VARCHAR(50) NOT NULL DEFAULT 'LAINNYA' AFTER report_type,
    ADD COLUMN cashflow_default_component VARCHAR(30) NOT NULL DEFAULT 'NONE' AFTER report_section,
    ADD COLUMN auxiliary_type VARCHAR(30) NOT NULL DEFAULT 'NONE' AFTER cashflow_default_component,
    ADD COLUMN is_cash TINYINT(1) NOT NULL DEFAULT 0 AFTER auxiliary_type,
    ADD COLUMN is_bank TINYINT(1) NOT NULL DEFAULT 0 AFTER is_cash,
    ADD COLUMN is_control_account TINYINT(1) NOT NULL DEFAULT 0 AFTER is_bank,
    ADD COLUMN allow_direct_posting TINYINT(1) NOT NULL DEFAULT 1 AFTER is_control_account,
    ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER allow_direct_posting;

UPDATE coa_accounts
SET normal_balance = CASE
        WHEN account_type IN ('LIABILITY','EQUITY','REVENUE') THEN 'CREDIT'
        ELSE 'DEBIT'
    END,
    report_type = CASE
        WHEN account_type IN ('ASSET','LIABILITY') THEN 'BALANCE_SHEET'
        WHEN account_type = 'EQUITY' THEN 'EQUITY'
        WHEN account_type IN ('REVENUE','EXPENSE') THEN 'PROFIT_LOSS'
        ELSE 'OFF_BALANCE'
    END,
    report_section = CASE
        WHEN account_type = 'ASSET' AND account_category = 'CURRENT_ASSET' THEN 'ASET_LANCAR'
        WHEN account_type = 'ASSET' AND account_category = 'FIXED_ASSET' THEN 'ASET_TETAP'
        WHEN account_type = 'ASSET' THEN 'ASET_LAINNYA'
        WHEN account_type = 'LIABILITY' AND account_category = 'CURRENT_LIABILITY' THEN 'KEWAJIBAN_LANCAR'
        WHEN account_type = 'LIABILITY' AND account_category = 'LONG_TERM_LIABILITY' THEN 'KEWAJIBAN_JANGKA_PANJANG'
        WHEN account_type = 'LIABILITY' THEN 'KEWAJIBAN_LAINNYA'
        WHEN account_type = 'EQUITY' AND account_category = 'RETAINED_EARNINGS' THEN 'LABA_DITAHAN'
        WHEN account_type = 'EQUITY' THEN 'EKUITAS'
        WHEN account_type = 'REVENUE' AND account_category = 'OPERATING_REVENUE' THEN 'PENDAPATAN_USAHA'
        WHEN account_type = 'REVENUE' AND account_category = 'NON_OPERATING_REVENUE' THEN 'PENDAPATAN_NON_USAHA'
        WHEN account_type = 'REVENUE' THEN 'PENDAPATAN_LAIN'
        WHEN account_type = 'EXPENSE' AND account_category = 'ADMIN_EXPENSE' THEN 'BEBAN_ADMINISTRASI'
        WHEN account_type = 'EXPENSE' AND account_category = 'OTHER_EXPENSE' THEN 'BEBAN_LAIN'
        WHEN account_type = 'EXPENSE' AND account_category = 'COST_OF_GOODS_SOLD' THEN 'HPP'
        WHEN account_type = 'EXPENSE' AND account_category = 'TAX_EXPENSE' THEN 'BEBAN_PAJAK'
        WHEN account_type = 'EXPENSE' THEN 'BEBAN_OPERASIONAL'
        ELSE 'LAINNYA'
    END,
    cashflow_default_component = 'NONE',
    auxiliary_type = CASE
        WHEN account_type = 'ASSET' AND account_category = 'FIXED_ASSET' THEN 'ASSET'
        WHEN account_type = 'ASSET' AND account_category = 'CURRENT_ASSET' AND LOWER(account_name) LIKE '%piutang%' THEN 'RECEIVABLE'
        WHEN account_type = 'LIABILITY' AND LOWER(account_name) LIKE '%utang%' THEN 'PAYABLE'
        WHEN account_type = 'ASSET' AND LOWER(account_name) LIKE '%persediaan%' THEN 'INVENTORY'
        ELSE 'NONE'
    END,
    is_cash = CASE WHEN account_type = 'ASSET' AND account_category = 'CURRENT_ASSET' AND LOWER(account_name) LIKE '%kas%' THEN 1 ELSE 0 END,
    is_bank = CASE WHEN account_type = 'ASSET' AND account_category = 'CURRENT_ASSET' AND LOWER(account_name) LIKE '%bank%' THEN 1 ELSE 0 END,
    is_control_account = CASE
        WHEN account_type = 'ASSET' AND LOWER(account_name) LIKE '%piutang%' THEN 1
        WHEN account_type = 'LIABILITY' AND LOWER(account_name) LIKE '%utang%' THEN 1
        WHEN account_type = 'ASSET' AND LOWER(account_name) LIKE '%persediaan%' THEN 1
        WHEN account_type = 'ASSET' AND account_category = 'FIXED_ASSET' THEN 1
        ELSE 0
    END,
    allow_direct_posting = CASE WHEN is_header = 1 THEN 0 ELSE 1 END,
    display_order = COALESCE(display_order, 0);

UPDATE coa_accounts
SET is_bank = 0
WHERE is_cash = 1;
