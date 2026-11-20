{{-- فیلتر فشرده فرصت‌ها --}}
<style>
    /* عنوان فیلترها کامل حذف */
    .fi-ta-filters .fi-section-header,
    .fi-ta-filters .fi-section-header-heading,
    .fi-ta-filters > .fi-section > header,
    .fi-ta-filters header.fi-section-header {
        display: none !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        overflow: hidden !important;
        visibility: hidden !important;
    }

    .fi-ta-filters > .fi-section,
    .fi-ta-filters .fi-section {
        box-shadow: none !important;
        background: transparent !important;
        border: 0 !important;
    }

    .fi-ta-filters .fi-fo-field-wrp-label,
    .fi-ta-filters label.fi-fo-field-wrp-label {
        display: none !important;
    }

    .fi-ta-filters .fi-section-content,
    .fi-ta-filters .fi-section-content-ctn {
        padding: 0.35rem 0.5rem !important;
    }

    /* حذف فیلتر کوچک، در جریان همان ردیف */
    .fi-ta-filters-actions {
        display: inline-flex !important;
        align-items: center !important;
        margin: 0 !important;
        padding: 0.25rem 0.35rem !important;
        position: static !important;
    }
    .fi-ta-filters-actions .fi-btn,
    .fi-ta-filters-actions a,
    .fi-ta-filters-actions button {
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        padding: 0.3rem 0.45rem !important;
        min-height: 0 !important;
        height: auto !important;
    }

    /* جستجو پهن‌تر از بقیه روی دسکتاپ */
    @media (min-width: 1024px) {
        .fi-ta-filters .fi-sc-grid,
        .fi-ta-filters .fi-grid,
        .fi-ta-filters [class*="grid-cols"] {
            grid-template-columns: minmax(16rem, 2.2fr) repeat(4, minmax(0, 1fr)) auto !important;
            gap: 0.5rem !important;
            align-items: center !important;
        }
    }
</style>
