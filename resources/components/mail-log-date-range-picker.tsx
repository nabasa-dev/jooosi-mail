import { DateRangePicker } from "@/components/date-range-picker"
import type { MailLogDateRangeFilter } from "@/components/mail-log-table-types"

type MailLogDateRangePickerProps = {
  value?: MailLogDateRangeFilter
  onChange: (value?: MailLogDateRangeFilter) => void
}

export function MailLogDateRangePicker({
  value,
  onChange,
}: MailLogDateRangePickerProps) {
  return (
    <DateRangePicker
      id="mail-log-date-range"
      value={value}
      onChange={onChange}
      className="md:w-60"
    />
  )
}

export default MailLogDateRangePicker
