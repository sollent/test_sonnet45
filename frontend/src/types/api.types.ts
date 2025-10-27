export interface ApiResponse<T> {
  data: T
  status: number
}

export interface ValidationError {
  field: string
  message: string
}

export interface ErrorResponse {
  message: string
  code: number
  errors?: ValidationError[]
}

