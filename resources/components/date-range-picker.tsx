"use client"

import * as React from "react"
import { format } from "date-fns"
import type { DateRange } from "react-day-picker"

import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Field, FieldLabel } from "@/components/ui/field"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"
import { cn } from "@/lib/utils"
import Calendar03Icon from "~icons/hugeicons/calendar-03"

export type DateRangeFilter = {
  from?: string
  to?: string
}

type DateRangePickerProps = {
  id: string
  label?: string
  placeholder?: string
  value?: DateRangeFilter
  onChange: (value?: DateRangeFilter) => void
  className?: string
}

function parseDateValue(value?: string): Date | undefined {
  if (!value) {
    return undefined
  }

  const parsedValue = new Date(`${value}T00:00:00`)

  return Number.isNaN(parsedValue.getTime()) ? undefined : parsedValue
}

function toFilterValue(range?: DateRange): DateRangeFilter | undefined {
  if (!range?.from && !range?.to) {
    return undefined
  }

  return {
    from: range.from ? format(range.from, "yyyy-MM-dd") : undefined,
    to: range.to ? format(range.to, "yyyy-MM-dd") : undefined,
  }
}

export function DateRangePicker({
  id,
  label = "Date range",
  placeholder = "Pick a date",
  value,
  onChange,
  className,
}: DateRangePickerProps) {
  const selectedRange = React.useMemo<DateRange | undefined>(
    () => {
      const from = parseDateValue(value?.from)
      const to = parseDateValue(value?.to)

      if (!from && !to) {
        return undefined
      }

      return { from, to }
    },
    [value?.from, value?.to],
  )

  return (
    <Field className={cn("w-full md:w-60", className)}>
      <FieldLabel htmlFor={id} className="sr-only">
        {label}
      </FieldLabel>
      <Popover>
        <PopoverTrigger
          render={
            <Button
              variant="outline"
              id={id}
              data-empty={!selectedRange?.from}
              className="w-full justify-start rounded-md px-2.5 font-normal text-left data-[empty=true]:text-muted-foreground"
            />
          }
        >
          <Calendar03Icon data-icon="inline-start" />
          {selectedRange?.from ? (
            selectedRange.to ? (
              <>
                {format(selectedRange.from, "LLL dd, y")} - {format(selectedRange.to, "LLL dd, y")}
              </>
            ) : (
              format(selectedRange.from, "LLL dd, y")
            )
          ) : (
            <span>{placeholder}</span>
          )}
        </PopoverTrigger>
        <PopoverContent className="w-auto rounded-md p-0" align="start" sideOffset={6}>
          <Calendar
            mode="range"
            defaultMonth={selectedRange?.from}
            selected={selectedRange}
            onSelect={(range) => onChange(toFilterValue(range))}
            numberOfMonths={2}
          />
        </PopoverContent>
      </Popover>
    </Field>
  )
}

export default DateRangePicker
