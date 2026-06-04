import * as React from "react"

type UseAdminQueryOptions = {
  pollMs?: number
}

export function useAdminQuery<T>(
  loader: () => Promise<T>,
  options: UseAdminQueryOptions = {},
) {
  const [data, setData] = React.useState<T | null>(null)
  const [loading, setLoading] = React.useState(true)
  const [refreshing, setRefreshing] = React.useState(false)
  const [error, setError] = React.useState<string | null>(null)
  const loaderRef = React.useRef(loader)
  const dataRef = React.useRef<T | null>(null)

  React.useEffect(() => {
    loaderRef.current = loader
  }, [loader])

  React.useEffect(() => {
    dataRef.current = data
  }, [data])

  const refresh = React.useCallback(async () => {
    const initialLoad = dataRef.current === null

    if (initialLoad) {
      setLoading(true)
    } else {
      setRefreshing(true)
    }

    setError(null)

    try {
      const nextData = await loaderRef.current()
      setData(nextData)

      return nextData
    } catch (caughtError) {
      const message = caughtError instanceof Error ? caughtError.message : "An unexpected error occurred."
      setError(message)

      return null
    } finally {
      setLoading(false)
      setRefreshing(false)
    }
  }, [])

  React.useEffect(() => {
    void refresh()
  }, [refresh])

  React.useEffect(() => {
    if (!options.pollMs) {
      return
    }

    const intervalId = window.setInterval(() => {
      void refresh()
    }, options.pollMs)

    return () => {
      window.clearInterval(intervalId)
    }
  }, [options.pollMs, refresh])

  return {
    data,
    setData,
    loading,
    refreshing,
    error,
    refresh,
  }
}
