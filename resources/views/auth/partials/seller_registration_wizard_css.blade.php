<style>
    .seller-reg-wizard {
        width: 100%;
        max-width: none;
        border: 1px solid rgba(41, 41, 51, 0.08);
        border-radius: 18px;
        background: #fff;
        overflow: hidden;
    }

    .seller-reg-page-shell {
        width: 100%;
    }

    .seller-reg-wizard__steps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        padding: 14px 16px;
        background: rgba(52, 144, 243, 0.06);
    }

    .seller-reg-wizard__step {
        display: flex;
        align-items: center;
        width: 100%;
        min-width: 0;
        padding: 14px 16px;
        border: 1px solid rgba(52, 144, 243, 0.12);
        border-radius: 12px;
        background: #fff;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .seller-reg-wizard__step.is-active,
    .seller-reg-wizard__step.is-complete {
        border-color: var(--primary);
        box-shadow: 0 10px 24px rgba(52, 144, 243, 0.12);
    }

    .seller-reg-wizard__step-index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        margin-right: 10px;
        border-radius: 8px;
        background: rgba(52, 144, 243, 0.12);
        color: var(--primary);
        font-size: 13px;
        font-weight: 700;
        flex: 0 0 auto;
    }

    .seller-reg-wizard__step.is-active .seller-reg-wizard__step-index,
    .seller-reg-wizard__step.is-complete .seller-reg-wizard__step-index {
        background: var(--primary);
        color: #fff;
    }

    .seller-reg-wizard__step-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--soft-dark);
        line-height: 1.15;
        white-space: nowrap;
        overflow-wrap: normal;
    }

    .seller-reg-wizard__step-note {
        display: none;
        margin-top: 1px;
        font-size: 11px;
        color: var(--gray-dark);
        line-height: 1.2;
    }

    .seller-reg-wizard__body {
        padding: 18px 20px 18px;
    }

    .seller-reg-error-summary {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid rgba(220, 53, 69, 0.2);
        border-radius: 12px;
        background: rgba(220, 53, 69, 0.06);
        color: #842029;
    }

    .seller-reg-error-summary__title {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .seller-reg-error-summary__list {
        margin: 0;
        padding-left: 18px;
    }

    .seller-reg-error-summary__list li {
        font-size: 13px;
        line-height: 1.4;
        margin-bottom: 4px;
    }

    .seller-reg-wizard__panel {
        display: none;
    }

    .seller-reg-wizard__panel.is-active {
        display: block;
    }

    .seller-reg-wizard__title {
        margin-bottom: 2px;
        font-size: 22px;
        font-weight: 700;
        color: var(--soft-dark);
    }

    .seller-reg-wizard__subtitle {
        margin-bottom: 16px;
        font-size: 14px;
        color: var(--gray-dark);
    }

    .seller-reg-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 16px;
    }

    .seller-reg-grid > * {
        min-width: 0;
    }

    .seller-reg-grid .form-group {
        margin-bottom: 0;
    }

    .seller-reg-grid__full {
        grid-column: 1 / -1;
        width: 100%;
    }

    .seller-reg-grid__half {
        grid-column: span 1;
    }

    .seller-reg-wizard__actions {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-top: 18px;
    }

    .seller-reg-wizard__actions--end {
        justify-content: flex-end;
    }

    .seller-reg-helper {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: var(--gray-dark);
    }

    /* Ensure email and student status inputs are full width */
    input[name="email"],
    select#student_status,
    select#school_id {
        width: 100%;
        max-width: 100%;
    }

    .seller-reg-wizard .form-control,
    .seller-reg-wizard .custom-file-input,
    .seller-reg-wizard .custom-file-label {
        width: 100%;
        min-width: 0;
        min-height: 50px;
        padding-top: 0.625rem;
        padding-bottom: 0.625rem;
        font-size: 14px !important;
        overflow: hidden;
    }

    .seller-reg-wizard select.form-control {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .seller-reg-wizard select.form-control option {
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .seller-reg-wizard .form-control::placeholder {
        font-size: 14px;
    }

    .seller-reg-wizard .form-group label {
        margin-bottom: 4px;
        font-size: 14px !important;
        font-weight: 600;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        word-break: break-word;
    }

    .seller-reg-wizard .invalid-feedback {
        font-size: 13px;
        margin-top: 4px;
    }

    .seller-reg-wizard .btn {
        min-height: 50px;
        font-size: 14px;
        white-space: nowrap;
    }

    @media (min-width: 992px) {
        .seller-reg-page-shell {
            max-width: none;
            margin-left: 0;
            margin-right: auto;
        }

        .seller-reg-wizard {
            width: min(100%, 1280px);
        }

        .seller-reg-wizard__steps {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
        }

        .seller-reg-wizard__step {
            flex: 1 1 0;
        }

        .seller-reg-wizard__step-label {
            font-size: 15px;
        }

        .seller-reg-wizard__title {
            font-size: 26px;
        }

        .seller-reg-wizard__subtitle {
            font-size: 15px;
        }

        .seller-reg-wizard .form-group label {
            font-size: 15px !important;
        }

        .seller-reg-wizard .form-control,
        .seller-reg-wizard .custom-file-input,
        .seller-reg-wizard .custom-file-label,
        .seller-reg-wizard .form-control::placeholder,
        .seller-reg-wizard .btn {
            font-size: 15px !important;
        }
    }

    @media (max-width: 1199px) {
        .seller-reg-wizard__steps {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .seller-reg-wizard__body {
            padding: 16px 18px 16px;
        }
    }

    @media (max-width: 767px) {
        .seller-reg-wizard__steps,
        .seller-reg-grid {
            grid-template-columns: 1fr;
        }

        .seller-reg-wizard__actions {
            flex-direction: column;
        }

        .seller-reg-wizard__step-note {
            display: block;
        }

        .seller-reg-wizard__title {
            font-size: 18px;
        }

        .seller-reg-wizard__subtitle {
            font-size: 13px;
        }
    }
</style>
