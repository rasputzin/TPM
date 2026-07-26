export function StatusBadge({ status }: { status: string }) {
  return <span className={`tpm-badge tpm-badge-${status}`}>{status}</span>
}
