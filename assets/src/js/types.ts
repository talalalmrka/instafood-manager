export interface ResultData {
  type: "success" | "error";
  message: string;
  summary?: Record<string, any>;
}

export type CssClassCondition = string | number | boolean | null | undefined;

export type CssClassValue =
  | string
  | false
  | null
  | undefined
  | Record<string, CssClassCondition>
  | CssClassValue[];

export type HtmlAttrValue = string | number | boolean | null | undefined | any;

export type HtmlAttrs = Record<string, HtmlAttrValue>;

export type Content =
  | string
  | Promise<string>
  | number
  | Promise<number>
  | Content[];
