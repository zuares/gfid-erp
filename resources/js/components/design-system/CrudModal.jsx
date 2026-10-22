import { useEffect, useId, useRef, useState } from 'react';

const defaultValues = (fields, values = {}) => fields.reduce((result, field) => {
    if (field.type === 'divider' || field.type === 'info') return result;
    result[field.name] = values[field.name] ?? field.defaultValue ?? (field.type === 'toggle' || field.type === 'checkbox' ? false : '');
    return result;
}, {});

function FieldMessage({ field, error }) {
    if (error) return <span className="ds-crud-error" role="alert">{error}</span>;
    if (field.hint) return <span className="ds-crud-hint">{field.hint}</span>;
    return null;
}

export function selectAllOnFocus(event) {
    if (!event.currentTarget.value) return;
    window.requestAnimationFrame(() => event.currentTarget.select());
}

export function selectAllOnMouseDown(event) {
    if (!event.currentTarget.value) return;
    event.currentTarget.focus();
    event.currentTarget.select();
    event.preventDefault();
}

function FieldControl({ field, value, onChange, error }) {
    const fieldId = `crud-${field.name}`;
    const describedBy = error || field.hint ? `${fieldId}-message` : undefined;
    const inputRef = useRef(null);
    const [isComboboxOpen, setIsComboboxOpen] = useState(false);
    const [comboboxQuery, setComboboxQuery] = useState(() => field.options?.find((option) => option.value === value)?.label || value || '');
    const fieldType = field.type || 'text';
    const isTextInput = ['text', 'email', 'tel', 'url', 'password'].includes(fieldType);
    const selectedOption = field.options?.find((option) => option.value === value);
    const filteredOptions = field.options?.filter((option) => option.label.toLowerCase().includes(comboboxQuery.toLowerCase())) || [];
    const selectAllProps = field.selectAllOnFocus === false ? {} : { onFocus: selectAllOnFocus, onMouseDown: selectAllOnMouseDown };
    const commonProps = {
        id: fieldId,
        name: field.name,
        value: value ?? '',
        onChange: (event) => onChange(field.name, event.target.value),
        'aria-invalid': error ? 'true' : undefined,
        'aria-describedby': describedBy,
        placeholder: field.placeholder,
        disabled: field.disabled,
    };

    useEffect(() => {
        if (field.type === 'combobox') setComboboxQuery(selectedOption?.label || value || '');
    }, [value, selectedOption?.label, field.type]);

    const clearValue = () => {
        onChange(field.name, field.type === 'toggle' || field.type === 'checkbox' ? false : '');
        inputRef.current?.focus();
    };

    if (field.type === 'textarea') return <div className="ds-crud-textarea-wrap"><textarea {...commonProps} {...selectAllProps} ref={inputRef} rows={field.rows || 3} maxLength={field.maxLength} data-crud-autofocus={field.autoFocus || undefined} />{field.maxLength && <span className="ds-crud-counter">{String(value || '').length}/{field.maxLength}</span>}</div>;
    if (field.type === 'select') return <div className="ds-crud-control"><i className={`bi ${field.icon || 'bi-chevron-expand'} ds-crud-leading-icon`} aria-hidden="true" /><select {...commonProps} ref={inputRef} data-crud-autofocus={field.autoFocus || undefined}>{field.placeholder && <option value="">{field.placeholder}</option>}{field.options?.map((option) => <option value={option.value} key={option.value}>{option.label}</option>)}</select></div>;
    if (field.type === 'combobox') return (
        <div className="ds-crud-combobox">
            <div className={`ds-crud-control ${isComboboxOpen ? 'is-open' : ''}`}>
                <i className={`bi ${field.icon || 'bi-search'} ds-crud-leading-icon`} aria-hidden="true" />
                <input ref={inputRef} id={fieldId} name={field.name} type="text" value={comboboxQuery} placeholder={field.placeholder} autoComplete="off" role="combobox" aria-expanded={isComboboxOpen} aria-controls={`${fieldId}-options`} aria-autocomplete="list" aria-invalid={error ? 'true' : undefined} aria-describedby={describedBy} disabled={field.disabled} data-crud-autofocus={field.autoFocus || undefined} onFocus={(event) => { if (field.selectAllOnFocus !== false) selectAllOnFocus(event); setIsComboboxOpen(true); }} onMouseDown={field.selectAllOnFocus === false ? undefined : selectAllOnMouseDown} onBlur={() => window.setTimeout(() => setIsComboboxOpen(false), 120)} onChange={(event) => { setComboboxQuery(event.target.value); setIsComboboxOpen(true); onChange(field.name, ''); }} onKeyDown={(event) => { if (event.key === 'Escape') { event.stopPropagation(); setIsComboboxOpen(false); } if (event.key === 'Enter' && filteredOptions[0]) { event.preventDefault(); onChange(field.name, filteredOptions[0].value); setComboboxQuery(filteredOptions[0].label); setIsComboboxOpen(false); } }} />
                {value && <button className="ds-crud-clear" type="button" onClick={clearValue} aria-label={`Hapus pilihan ${field.label}`}><i className="bi bi-x-circle-fill" aria-hidden="true" /></button>}
                <i className="bi bi-chevron-down ds-crud-trailing-icon" aria-hidden="true" />
            </div>
            {isComboboxOpen && <div className="ds-crud-combobox-menu" id={`${fieldId}-options`} role="listbox">{filteredOptions.length ? filteredOptions.map((option) => <button className={value === option.value ? 'is-selected' : ''} type="button" role="option" aria-selected={value === option.value} key={option.value} onMouseDown={(event) => event.preventDefault()} onClick={() => { onChange(field.name, option.value); setComboboxQuery(option.label); setIsComboboxOpen(false); }}>{option.label}<i className="bi bi-check2" aria-hidden="true" /></button>) : <span className="ds-crud-combobox-empty">Tidak ada hasil yang cocok.</span>}</div>}
        </div>
    );
    if (field.type === 'number' || field.type === 'currency') return <div className="ds-crud-control"><span className="ds-crud-input-prefix">{field.prefix || (field.type === 'currency' ? 'Rp' : '#')}</span><input {...commonProps} {...selectAllProps} ref={inputRef} type="number" inputMode="decimal" min={field.min} max={field.max} step={field.step || 1} data-crud-autofocus={field.autoFocus || undefined} />{field.suffix && <span className="ds-crud-input-suffix">{field.suffix}</span>}{field.clearable && value && <button className="ds-crud-clear" type="button" onClick={clearValue} aria-label={`Kosongkan ${field.label}`}><i className="bi bi-x-circle-fill" aria-hidden="true" /></button>}</div>;
    if (field.type === 'date') return <div className="ds-crud-control"><i className={`bi ${field.icon || 'bi-calendar3'} ds-crud-leading-icon`} aria-hidden="true" /><input {...commonProps} ref={inputRef} type="date" data-crud-autofocus={field.autoFocus || undefined} /></div>;
    if (field.type === 'file') return <label className="ds-crud-file" htmlFor={fieldId}><i className="bi bi-cloud-arrow-up" aria-hidden="true" /><span><strong>{value || 'Pilih file'}</strong><small>{field.accept || 'PNG, JPG atau PDF · maksimal 10 MB'}</small></span><input {...commonProps} value={undefined} type="file" onChange={(event) => onChange(field.name, event.target.files?.[0]?.name || '')} /></label>;
    if (field.type === 'toggle') return <label className="ds-crud-switch"><input id={fieldId} name={field.name} type="checkbox" checked={Boolean(value)} onChange={(event) => onChange(field.name, event.target.checked)} disabled={field.disabled} /><span aria-hidden="true" /><strong>{field.toggleLabel || (value ? 'Aktif' : 'Nonaktif')}</strong></label>;
    if (field.type === 'checkbox') return <label className="ds-crud-check"><input id={fieldId} name={field.name} type="checkbox" checked={Boolean(value)} onChange={(event) => onChange(field.name, event.target.checked)} disabled={field.disabled} /><span>{field.checkboxLabel}</span></label>;
    if (field.type === 'radio') return <div className="ds-crud-radio-group">{field.options?.map((option) => <label key={option.value}><input type="radio" name={field.name} value={option.value} checked={value === option.value} onChange={(event) => onChange(field.name, event.target.value)} /><span>{option.label}</span></label>)}</div>;
    return <div className={`ds-crud-control ${field.icon ? 'has-leading' : ''}`}>{field.icon && <i className={`bi ${field.icon} ds-crud-leading-icon`} aria-hidden="true" />}<input {...commonProps} {...selectAllProps} ref={inputRef} type={field.type || 'text'} maxLength={field.maxLength} autoComplete={field.autoComplete} data-crud-autofocus={field.autoFocus || undefined} />{field.suffix && <span className="ds-crud-input-suffix">{field.suffix}</span>}{isTextInput && field.clearable !== false && value && <button className="ds-crud-clear" type="button" onClick={clearValue} aria-label={`Kosongkan ${field.label}`}><i className="bi bi-x-circle-fill" aria-hidden="true" /></button>}</div>;
}

function CrudField({ field, values, errors, onChange }) {
    if (field.type === 'divider') return <div className="ds-crud-divider"><span>{field.label}</span></div>;
    if (field.type === 'info') return <div className="ds-crud-info"><i className={`bi ${field.icon || 'bi-info-circle'}`} aria-hidden="true" /><span>{field.content}</span></div>;

    const fieldId = `crud-${field.name}`;
    const error = errors[field.name];
    return (
        <div className={`ds-crud-field ds-crud-field-${field.type || 'text'} ${field.fullWidth ? 'ds-crud-field-full' : ''}`}>
            {field.type !== 'toggle' && field.type !== 'checkbox' && field.type !== 'radio' && <label htmlFor={fieldId}>{field.label}{field.required && <span aria-hidden="true"> *</span>}</label>}
            <FieldControl field={field} value={values[field.name]} onChange={onChange} error={error} />
            {field.quickOptions?.length > 0 && <div className="ds-crud-quick-options" aria-label={`Pilihan cepat untuk ${field.label}`}>{field.quickOptions.map((option) => <button className={String(values[field.name]) === String(option.value) ? 'is-selected' : ''} type="button" key={option.value} onClick={() => onChange(field.name, option.value)}>{option.label}</button>)}</div>}
            <div id={`${fieldId}-message`}><FieldMessage field={field} error={error} /></div>
        </div>
    );
}

export default function CrudModal({ open, mode = 'create', title, description, fields = [], initialValues, onClose, onSubmit, onDelete, submitLabel, loading = false }) {
    const titleId = useId();
    const descriptionId = useId();
    const modalRef = useRef(null);
    const previousActiveElement = useRef(null);
    const [values, setValues] = useState(() => defaultValues(fields, initialValues));
    const [errors, setErrors] = useState({});
    const isDelete = mode === 'delete';
    const isEdit = mode === 'edit';

    useEffect(() => {
        if (!open) return undefined;
        setValues(defaultValues(fields, initialValues));
        setErrors({});
        previousActiveElement.current = document.activeElement;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const focusTarget = modalRef.current?.querySelector('[data-crud-autofocus], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');
        focusTarget?.focus();
        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                if (loading) return;
                onClose?.();
            }
            if ((event.metaKey || event.ctrlKey) && event.key === 'Enter' && !isDelete) {
                event.preventDefault();
                modalRef.current?.querySelector('form')?.requestSubmit();
            }
            if (event.key === 'Tab' && modalRef.current) {
                const focusable = [...modalRef.current.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        };
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', handleKeyDown);
            previousActiveElement.current?.focus?.();
        };
    }, [open, fields, initialValues, isDelete, loading, onClose]);

    if (!open) return null;

    const handleChange = (name, value) => {
        setValues((current) => ({ ...current, [name]: value }));
        setErrors((current) => ({ ...current, [name]: '' }));
    };
    const handleSubmit = (event) => {
        event.preventDefault();
        const nextErrors = fields.reduce((result, field) => {
            if (field.required && !values[field.name]) result[field.name] = `${field.label} wajib diisi.`;
            return result;
        }, {});
        if (Object.keys(nextErrors).length) {
            setErrors(nextErrors);
            return;
        }
        onSubmit?.(values);
    };

    return (
        <div className="ds-modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && !loading && onClose?.()}>
            <section ref={modalRef} className={`ds-crud-modal ${isDelete ? 'is-delete' : ''}`} role="dialog" aria-modal="true" aria-labelledby={titleId} aria-describedby={description ? descriptionId : undefined} aria-busy={loading || undefined}>
                <header className="ds-crud-header">
                    <div className={`ds-crud-header-icon ${isDelete ? 'is-danger' : ''}`} aria-hidden="true"><i className={`bi ${isDelete ? 'bi-trash3' : isEdit ? 'bi-pencil-square' : 'bi-plus-lg'}`} /></div>
                    <div><p className="ds-crud-kicker">{isDelete ? 'Danger zone' : isEdit ? 'Update record' : 'New record'}</p><h2 id={titleId}>{title || (isDelete ? 'Hapus data?' : isEdit ? 'Edit data' : 'Tambah data')}</h2>{description && <p className="ds-crud-description" id={descriptionId}>{description}</p>}</div>
                    <button className="ds-icon-btn" type="button" onClick={onClose} disabled={loading} aria-label="Tutup modal"><i className="bi bi-x-lg" aria-hidden="true" /></button>
                </header>

                {isDelete ? (
                    <div className="ds-crud-delete-body"><div className="ds-crud-delete-illustration"><i className="bi bi-exclamation-triangle" aria-hidden="true" /></div><p>Data yang dihapus tidak dapat dikembalikan. Pastikan record ini sudah tidak digunakan di proses lain.</p></div>
                ) : (
                    <form id="crud-form" className="ds-crud-form" onSubmit={handleSubmit} noValidate><div className="ds-crud-fields">{fields.map((field) => <CrudField key={field.name || field.label} field={field} values={values} errors={errors} onChange={handleChange} />)}</div></form>
                )}

                <footer className="ds-crud-footer"><span className="ds-crud-required">{!isDelete && <><strong>*</strong> Wajib diisi <kbd>⌘/Ctrl + Enter</kbd> simpan</>}</span><div className="ds-action-row"><button className="ds-btn ds-btn-ghost" type="button" onClick={onClose} disabled={loading}>Batal</button>{isDelete ? <button className="ds-btn ds-btn-danger" type="button" onClick={onDelete} disabled={loading}>{loading ? 'Menghapus...' : 'Ya, hapus data'}</button> : <button className="ds-btn ds-btn-primary" type="submit" form="crud-form" disabled={loading}>{loading ? 'Menyimpan...' : submitLabel || (isEdit ? 'Simpan perubahan' : 'Simpan data')}</button>}</div></footer>
            </section>
        </div>
    );
}

export const crudFieldCatalog = [
    { type: 'text', label: 'Text input', note: 'Nama, kode, SKU' },
    { type: 'textarea', label: 'Textarea', note: 'Deskripsi panjang' },
    { type: 'select', label: 'Select', note: 'Pilih satu opsi' },
    { type: 'combobox', label: 'Search select', note: 'Cari lalu pilih' },
    { type: 'currency', label: 'Currency', note: 'Nominal rupiah' },
    { type: 'number', label: 'Number', note: 'Stok, kuantitas' },
    { type: 'date', label: 'Date picker', note: 'Tanggal transaksi' },
    { type: 'toggle', label: 'Toggle', note: 'Status aktif/nonaktif' },
    { type: 'checkbox', label: 'Checkbox', note: 'Persetujuan atau flag' },
    { type: 'radio', label: 'Radio group', note: 'Pilih satu dari beberapa' },
    { type: 'file', label: 'File upload', note: 'Lampiran atau gambar' },
];
